<?php

// config/rbac.php
return [
    'super_admin_role' => 'super_admin',
    'user_table' => 'users',
    'user_model' => env('RBAC_USER_MODEL', App\Models\User::class),

    'roles' => [
        'super_admin'       => ['type' => 'system', 'locked' => true, 'permissions' => '*'],
        'admin'             => ['type' => 'admin',  'permissions' => '*except_rbac'],
        'sales_rep'         => ['type' => 'staff',  'permissions' => [
            'view_clients','store_clients','update_clients',
            'view_quotations','store_quotations','update_quotations',
            'view_sales_orders','store_sales_orders','update_sales_orders',
            'view_products','view_invoices',
        ]],
        'inventory_manager' => ['type' => 'staff',  'permissions' => [
            'view_products','store_products','update_products','destroy_products',
            'view_stock_movements','store_stock_movements',
            'view_sales_orders',
        ]],
        'project_manager'   => ['type' => 'staff',  'permissions' => [
            'view_projects','store_projects','update_projects',
            'view_project_phases','update_project_phases',
            'view_clients','view_quotations',
        ]],
        'finance_officer'   => ['type' => 'staff',  'permissions' => [
            'view_invoices','store_invoices','update_invoices','destroy_invoices',
            'view_sales_orders','view_clients',
        ]],
        'client'            => ['type' => 'user', 'permissions' => [
            // self-scoped permissions — enforced by policies, not gates alone
            'portal_view_own_quotations','portal_view_own_orders',
            'portal_view_own_invoices','portal_view_own_projects',
        ]],
    ],

    'rbac_protected_permissions' => [
        'view_roles','store_roles','update_roles','destroy_roles','assign_permissions',
    ],
];