# Laravel RBAC

Fast, config-driven role-based access control for Laravel 10, 11, and 12.

## Installation

```bash
composer require vattra/laravel-rbac

php artisan vendor:publish --tag=rbac
php artisan migrate
php artisan rbac:install
```

The package uses the published `config/rbac.php` as the source of truth for
roles and permissions.

### Usage

Add the trait to your User model:

```php
use Vattra\Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

### Assign Roles

```php
$user->assignRole('admin');
$user->hasPermissionTo('view_invoices'); // bool
```

Permissions can also be checked through Laravel's authorization APIs:

```php
Gate::allows('view_invoices');
```

### Commands

| Command | Description |
| --- | --- |
| `rbac:install` | Sync configured permissions and roles |
| `rbac:seed` | Alias for `rbac:install` |
| `rbac:assign-role {user} {role}` | Assign an RBAC role to a user |
| `rbac:sync-permissions` | Sync configured permissions and roles |

If your application uses a different user model or users table, set
`RBAC_USER_MODEL` and `rbac.user_table` in the published configuration before
running the migration.
