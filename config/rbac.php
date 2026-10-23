<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Special role names
    |--------------------------------------------------------------------------
    | super_admin bypasses every permission and role check. admin is only used
    | by the `is_admin` gate and by the "*except_rbac" permission wildcard.
    */
    'super_admin_role' => 'super_admin',
    'admin_role' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | User model & keys
    |--------------------------------------------------------------------------
    | user_model:       null falls back to auth.providers.users.model.
    | user_table:       table the pivot foreign keys point at.
    | user_foreign_key: pivot column holding the user key.
    | user_key_type:    "id" (bigint), "uuid" or "ulid".
    */
    'user_model' => env('RBAC_USER_MODEL'),
    'user_table' => 'users',
    'user_foreign_key' => 'user_id',
    'user_key_type' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_permissions' => 'role_permissions',
        'user_roles' => 'user_roles',
        'user_permissions' => 'user_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent cache (optional)
    |--------------------------------------------------------------------------
    | When enabled, each user's resolved roles/permissions and the list of
    | registered permission names are cached. The cache is flushed
    | automatically when roles, permissions or assignments change.
    */
    'cache' => [
        'enabled' => env('RBAC_CACHE', false),
        'ttl' => 3600, // seconds
        'store' => null, // null = default cache store
        'prefix' => 'rbac',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gates & middleware
    |--------------------------------------------------------------------------
    | register_role_gates: define an `is_<role>` gate for every role below.
    | legacy_middleware_aliases: also register the old `role`/`permission`
    |                            aliases (they clash with other packages).
    */
    'register_role_gates' => true,
    'legacy_middleware_aliases' => false,

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | permissions: '*'            => every permission
    |              '*except_rbac' => every permission except
    |                                rbac_protected_permissions
    |              [ ... ]        => explicit list
    | locked:      true => the role cannot be deleted.
    | Permission names follow "<action>_<group>", e.g. view_posts.
    */
    'roles' => [
        'super_admin' => ['type' => 'system', 'locked' => true, 'permissions' => '*'],
        'admin' => ['type' => 'admin', 'locked' => true, 'permissions' => '*except_rbac'],
        'editor' => ['type' => 'staff', 'permissions' => [
            'view_posts', 'store_posts', 'update_posts', 'destroy_posts',
            'view_comments', 'update_comments', 'destroy_comments',
        ]],
        'user' => ['type' => 'user', 'permissions' => [
            'view_posts', 'store_comments',
        ]],
    ],

    'rbac_protected_permissions' => [
        'view_roles', 'store_roles', 'update_roles', 'destroy_roles', 'assign_permissions',
    ],
];
