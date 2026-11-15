<?php

/**
 * All permission keys used in the ERP system.
 * Each entry: 'key' => 'Human-readable label', grouped by module.
 */
return [
    'dashboard' => [
        'label' => 'Dashboard',
        'permissions' => [
            'dashboard.view' => 'View Dashboard',
        ],
    ],
    'pos' => [
        'label' => 'POS (Retail)',
        'permissions' => [
            'pos.view' => 'View POS',
            'pos.sale' => 'Process Sales',
        ],
    ],
    'orders' => [
        'label' => 'Orders',
        'permissions' => [
            'orders.view'    => 'View Orders',
            'orders.create'  => 'Create Orders',
            'orders.edit'    => 'Edit Draft/Pending Orders',
            'orders.payment' => 'Record Payments',
            'orders.delete'  => 'Delete Orders',
        ],
    ],
    'inventory' => [
        'label' => 'Inventory',
        'permissions' => [
            'inventory.view' => 'View Inventory',
            'inventory.edit' => 'Edit Inventory',
            'inventory.delete' => 'Delete Inventory',
        ],
    ],
    'products' => [
        'label' => 'Products',
        'permissions' => [
            'products.view'   => 'View Products',
            'products.create' => 'Create Products',
            'products.edit'   => 'Edit Products / Categories / Brands / Units / Labels',
            'products.delete' => 'Delete Products',
        ],
    ],
    'purchases' => [
        'label' => 'Purchases',
        'permissions' => [
            'purchases.view'   => 'View Purchases',
            'purchases.create' => 'Create Purchases',
            'purchases.edit' => 'Edit Purchases / Suppliers',
            'purchases.delete' => 'Delete Purchases / Suppliers',
        ],
    ],
    'customers' => [
        'label' => 'Customers',
        'permissions' => [
            'customers.view'   => 'View Customers',
            'customers.create' => 'Create Customers',
            'customers.edit' => 'Edit Customers',
            'customers.delete' => 'Delete Customers',
        ],
    ],
    'sales_persons' => [
        'label' => 'Sales Persons',
        'permissions' => [
            'sales_persons.view'   => 'View Sales Persons',
            'sales_persons.create' => 'Create Sales Persons',
            'sales_persons.edit' => 'Edit Sales Persons / Routes / Commissions',
            'sales_persons.delete' => 'Delete Sales Persons / Routes',
        ],
    ],
    'reports' => [
        'label' => 'Reports',
        'permissions' => [
            'reports.view'  => 'View Reports',
            'reports.sales' => 'View Sales Reports',
        ],
    ],
    'manufacturing' => [
        'label' => 'Manufacturing',
        'permissions' => [
            'manufacturing.view'       => 'View Manufacturing',
            'manufacturing.edit'       => 'Edit Manufacturing / Lines / Recipes',
            'manufacturing.delete'     => 'Delete Manufacturing / Lines / Recipes / Costs',
            'manufacturing.jobs'       => 'Manage Jobs',
            'manufacturing.costs'      => 'View / manage production costs',
            'manufacturing.cutting'    => 'Cutting Section',
            'manufacturing.production' => 'Line Production (Hourly)',
            'manufacturing.washing'    => 'Washing / Gate Pass',
            'manufacturing.qc'         => 'QC / Packing',
            'manufacturing.final'      => 'Final Checking',
        ],
    ],
    'employees' => [
        'label' => 'Employees',
        'permissions' => [
            'employees.view'   => 'View Employees',
            'employees.create' => 'Create Employees',
            'employees.edit' => 'Edit Employees',
            'employees.delete' => 'Delete Employees / Documents (including bulk delete)',
        ],
    ],
    'hrm' => [
        'label' => 'HRM',
        'permissions' => [
            'hrm.view'       => 'View HRM',
            'hrm.edit'       => 'Edit HRM / Attendance / Payroll',
            'hrm.delete'     => 'Delete HRM Records',
            'hrm.attendance' => 'Attendance',
            'hrm.leave'      => 'Leave Management',
            'hrm.payroll'    => 'Payroll',
        ],
    ],
    'expenses' => [
        'label' => 'Expenses',
        'permissions' => [
            'expenses.view'   => 'View Expenses',
            'expenses.create' => 'Create Expenses',
            'expenses.edit' => 'Edit Expenses / Expense Categories',
            'expenses.delete' => 'Delete Expenses / Expense Categories (including bulk delete)',
        ],
    ],
    'vehicles' => [
        'label' => 'Vehicle Management',
        'permissions' => [
            'vehicles.view'   => 'View Vehicles',
            'vehicles.manage' => 'Manage Vehicles & Drivers',
            'vehicles.edit' => 'Edit Vehicles / Drivers / Jobs',
            'vehicles.delete' => 'Delete Vehicles / Drivers / Assignments / Expenses',
        ],
    ],
    'cheque_whatsapp' => [
        'label' => 'Cheque / WhatsApp Settings',
        'permissions' => [
            'cheque.settings' => 'Cheque & WhatsApp Settings',
            'cheque.edit' => 'Edit Cheque & WhatsApp Settings',
        ],
    ],
    'accounting' => [
        'label' => 'Accounting',
        'permissions' => [
            'accounting.view' => 'View Accounting',
            'accounting.manage' => 'Manage Accounting & Journal Entries',
            'accounting.edit' => 'Edit Accounting Records',
            'accounting.delete' => 'Delete Accounting Records',
        ],
    ],
    'users' => [
        'label' => 'User Management',
        'permissions' => [
            'users.view'   => 'View Users',
            'users.create' => 'Create Users',
            'users.edit'   => 'Edit Users',
            'users.delete' => 'Delete Users',
            'roles.view'   => 'View Roles',
            'roles.create' => 'Manage Roles (administrators only)',
        ],
    ],
    'essentials' => [
        'label' => 'Essentials',
        'permissions' => [
            'essentials.edit' => 'Edit Essentials',
            'essentials.delete' => 'Delete Memos / Documents / Reminders',
        ],
    ],
];
