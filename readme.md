# Laravel RBAC

Fast, config-driven role-based access control for Laravel 10, 11, 12 and 13.

- Roles and permissions defined in `config/rbac.php`, synced with one command
- `HasRolesAndPermissions` trait for your User model
- Gate integration, `rbac.role` / `rbac.permission` middleware, Blade directives
- Policy-safe: permissions never bypass your policies
- Configurable table names and user keys (bigint, UUID, ULID)
- Optional persistent cache, flushed automatically

**Requirements:** PHP 8.2+ (Laravel 13 needs PHP 8.3+), Laravel 10-13.

## Installation

```bash
composer require vattra/laravel-rbac

php artisan vendor:publish --tag=rbac-config   # optional but recommended
php artisan migrate                            # migrations load from the package
php artisan rbac:install                       # sync roles + permissions from config
php artisan rbac:check                         # verify the setup
```

Add the trait to your User model:

```php
use Vattra\Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

Publish tags: `rbac-config`, `rbac-migrations`, and `rbac` (both).

## Configuration

`config/rbac.php` (generic defaults: `super_admin`, `admin`, `editor`, `user`).

| Key | Purpose |
| --- | --- |
| `super_admin_role` | Role that bypasses every permission/role check (default `super_admin`) |
| `admin_role` | Used by the `is_admin` gate and `*except_rbac` (default `admin`) |
| `user_model` | User class; `null` falls back to `auth.providers.users.model` (`RBAC_USER_MODEL` env) |
| `user_table`, `user_foreign_key`, `user_key_type` | Users table, pivot column, `id` / `uuid` / `ulid` |
| `tables.*` | Names of the five tables |
| `cache.enabled`, `cache.ttl`, `cache.store`, `cache.prefix` | Persistent cache (`RBAC_CACHE` env) |
| `register_role_gates` | Define an `is_<role>` gate per configured role |
| `legacy_middleware_aliases` | Also register `role` / `permission` aliases |
| `roles` | Role definitions (see below) |
| `rbac_protected_permissions` | Excluded from the `*except_rbac` wildcard |

```php
'roles' => [
    'super_admin' => ['type' => 'system', 'locked' => true, 'permissions' => '*'],
    'admin'       => ['type' => 'admin', 'locked' => true, 'permissions' => '*except_rbac'],
    'editor'      => ['type' => 'staff', 'permissions' => ['view_posts', 'store_posts']],
],
```

Permission names follow `<action>_<group>`; the group is the last `_` segment
(`view_blog_posts` -> `posts`). `locked` roles cannot be deleted.

**UUID users / custom tables.** Set these *before* running `php artisan migrate`:

```php
'user_table' => 'members',
'user_foreign_key' => 'member_id',
'user_key_type' => 'uuid',            // 'id' | 'uuid' | 'ulid'
'tables' => ['roles' => 'acl_roles', /* ... */],
```

## Usage

### Roles and permissions

```php
$user->assignRole('admin', 'editor');
$user->removeRole('editor');
$user->syncRoles(['user']);

$user->hasRole('admin');                 // any of the given roles
$user->hasAnyRole('admin', 'editor');
$user->hasAllRoles('admin', 'editor');
$user->getRoleNames();                   // Collection

$user->givePermissionTo('destroy_posts'); // direct permission
$user->revokePermissionTo('destroy_posts');
$user->hasPermissionTo('view_posts');
$user->hasAnyPermission(['a', 'b']);
$user->hasAllPermissions(['a', 'b']);
$user->getAllPermissions();              // via roles + direct

User::role('editor')->get();             // also 'admin|editor'
User::permission('view_posts')->get();   // via role, direct or super admin
```

The super admin passes every `hasPermissionTo()` / `hasAnyPermission()` check,
including permission names that don't exist.

Role helpers: `Role::givePermissionTo()`, `revokePermissionTo()`, `syncPermissions()`.
Look things up with the facade: `Rbac::findRole('admin')`, `Rbac::findPermission('view_posts')`.

### Middleware

```php
Route::middleware('rbac.role:admin|editor')->group(...);       // or admin,editor
Route::middleware('rbac.permission:view_posts')->get(...);     // or a|b
```

Any of the listed roles/permissions is enough, and the super admin always passes.
Guests get a redirect to `login` (or 401 for JSON requests / apps with no `login`
route); authenticated users who lack access get 403.

### Blade

```blade
@role('admin|editor') ... @elserole('user') ... @endrole
@hasanyrole('admin', 'editor') ... @endhasanyrole
@permission('view_posts') ... @endpermission
```

### Gates

Permission names work as abilities, and `is_admin`, `is_staff` and `is_<role>` gates exist:

```php
Gate::allows('view_posts');
$user->can('view_posts');
@can('view_posts') ... @endcan
```

The package's `Gate::before` only ever *grants*. It does nothing for guests, and it
**never overrides a policy**: when a policy applies to the arguments (e.g.
`Gate::allows('view_posts', $post)` and `Post` has a policy) the policy decides. The
super admin bypasses everything that isn't governed by a policy.

### Policies

Extend `RbacPolicy` to let the super admin through your policy automatically and
use the `hasPermission()` helper:

```php
use Vattra\Rbac\Policies\RbacPolicy;

class PostPolicy extends RbacPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $this->hasPermission($user, 'update_posts') && $user->id === $post->user_id;
    }
}
```

### Cache

Set `RBAC_CACHE=true` to cache each user's roles/permissions and the registered
permission list (`rbac.cache.ttl`, `rbac.cache.store`). Changes made through the
trait, the `Role`/`Permission` models, `Role::givePermissionTo()` and the install
commands flush it automatically. If you write to the pivot tables with raw SQL,
call `Rbac::flushCache()`.

## Commands

| Command | Description |
| --- | --- |
| `rbac:install` | Create/sync roles and permissions from config |
| `rbac:seed` | Alias for `rbac:install` |
| `rbac:sync-permissions` | Alias for `rbac:install` |
| `rbac:assign-role {user} {role} [--sync]` | Assign a role by key or email (uses `rbac.user_model`) |
| `rbac:check` | Verify tables, user model, gates, seeded roles (non-zero exit on problems) |

`rbac:install` is the source of truth for configured roles: it **re-syncs each
configured role's permissions**, replacing any changes made to them in the database.

## Testing

```bash
composer install
composer test
```

The suite uses PHPUnit and Orchestra Testbench (in-memory SQLite). CI runs PHP 8.2-8.4 against Laravel 10-12 (plus 13 on PHP 8.3+).

## Upgrade notes (0.1 -> 0.2)

- **`rbac:migrate` was removed** (its `--fresh` flag ran `migrate:fresh`). Use `php artisan migrate`.
- **Middleware aliases are now `rbac.role` and `rbac.permission`.** To keep the old
  names, set `legacy_middleware_aliases` to `true`.
- **Gate::before no longer bypasses policies.** If you relied on a permission
  overriding a policy, move that check into the policy.
- **Soft deletes were removed** from roles/permissions. The new migration purges
  soft-deleted rows and drops `deleted_at`; run `php artisan migrate`.
- The hardcoded `is_sales_rep`, `is_inventory_manager`, `is_project_manager`,
  `is_finance_officer`, `is_client` gates are gone. `is_<role>` gates are now
  generated from your `roles` config, so those names return if you keep those roles.
- The default config is generic. If you published the old ERP config, keep it (the
  example below).
- Add `'admin_role' => 'admin'` and the new keys if you published the config earlier.
- Folder `Database/` is now `database/`; the seeders were removed (use `rbac:install`).

## Example: ERP-style roles

```php
'roles' => [
    'super_admin'       => ['type' => 'system', 'locked' => true, 'permissions' => '*'],
    'admin'             => ['type' => 'admin',  'permissions' => '*except_rbac'],
    'sales_rep'         => ['type' => 'staff',  'permissions' => [
        'view_clients', 'store_clients', 'update_clients',
        'view_quotations', 'store_quotations', 'update_quotations',
        'view_sales_orders', 'store_sales_orders', 'update_sales_orders',
        'view_products', 'view_invoices',
    ]],
    'inventory_manager' => ['type' => 'staff',  'permissions' => [
        'view_products', 'store_products', 'update_products', 'destroy_products',
        'view_stock_movements', 'store_stock_movements', 'view_sales_orders',
    ]],
    'project_manager'   => ['type' => 'staff',  'permissions' => [
        'view_projects', 'store_projects', 'update_projects',
        'view_project_phases', 'update_project_phases', 'view_clients', 'view_quotations',
    ]],
    'finance_officer'   => ['type' => 'staff',  'permissions' => [
        'view_invoices', 'store_invoices', 'update_invoices', 'destroy_invoices',
        'view_sales_orders', 'view_clients',
    ]],
    'client'            => ['type' => 'user',   'permissions' => [
        // self-scoped permissions: enforce ownership in policies
        'portal_view_own_quotations', 'portal_view_own_orders',
        'portal_view_own_invoices', 'portal_view_own_projects',
    ]],
],
```

## License

MIT. See [LICENSE](LICENSE).

## Original 
https://t.me/VattraF5
