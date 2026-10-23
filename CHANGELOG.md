# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased] - 0.2.0

### Security
- `Gate::before` no longer runs for guests (the callback is typed `Authenticatable`).
- `Gate::before` no longer bypasses policies: it only grants when no policy
  applies to the arguments, and (for non-super-admins) the ability is a
  registered permission.
- `RoleMiddleware` now lets `super_admin` through and answers guests with
  401/redirect instead of 403.

### Removed
- **`rbac:migrate`**. Its `--fresh` option ran `migrate:fresh`, dropping every
  table. Migrations load automatically; run `php artisan migrate`.
- `PermissionSeeder` / `RoleSeeder` (duplicates of `rbac:install`).
- `SoftDeletes` on `Role` and `Permission` (soft-deleted rows blocked unique names).
  An upgrade migration purges soft-deleted rows and drops `deleted_at`.
- Hardcoded `is_sales_rep`, `is_inventory_manager`, `is_project_manager`,
  `is_finance_officer`, `is_client` gates. `is_<role>` gates are now generated
  from `rbac.roles` (`rbac.register_role_gates`).

### Changed
- **Middleware aliases are now `rbac.role` and `rbac.permission`** (support
  `a|b`). Set `rbac.legacy_middleware_aliases` to keep `role` / `permission`.
- Default config is generic (`super_admin`, `admin`, `editor`, `user`); the ERP
  example moved to the README.
- Permission `group` is the last `_` segment (`view_blog_posts` -> `posts`).
- `rbac:check` returns proper exit codes and no longer reports false "Gates missing".
- `rbac:assign-role` uses `rbac.user_model` and supports UUID/ULID keys.
- Folder `Database/` renamed to `database/`; `LICENSCE` renamed to `LICENSE`.
- The `Rbac` facade now resolves `Vattra\Rbac\RbacManager`.

### Added
- Configurable table names (`rbac.tables.*`), `rbac.user_foreign_key`, `rbac.user_key_type`.
- `rbac.admin_role`; super admin/admin names read from config everywhere.
- `Rbac::findRole()`, `Rbac::findPermission()`, `Rbac::permissionExists()`, `Rbac::flushCache()`.
- Trait: `removeRole`, `revokePermissionTo`, `hasAllPermissions`, `hasAllRoles`,
  `hasAnyRole`, `getRoleNames`, `isSuperAdmin`, scopes `role()` and `permission()`.
- `Role::givePermissionTo`, `revokePermissionTo`, `syncPermissions`.
- Blade directives `@role`, `@hasanyrole`, `@permission`.
- Optional persistent cache (`rbac.cache.*`), flushed automatically.
- PHPUnit + Orchestra Testbench test suite and GitHub Actions matrix.

## [0.1.0]
- Initial release.
