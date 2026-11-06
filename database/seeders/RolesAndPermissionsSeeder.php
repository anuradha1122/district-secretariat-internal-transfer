<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // User administration
            'view users',
            'manage users',

            // Master data
            'view offices',
            'manage offices',
            'view services',
            'manage services',

            // Employee eligibility
            'view eligible employees',
            'import eligible employees',
            'manage eligible employees',

            // Transfer cycles
            'view transfer cycles',
            'manage transfer cycles',

            // Applications
            'create own applications',
            'view own applications',
            'view office applications',
            'review office applications',
            'view district applications',
            'review district applications',

            // Mutual transfers
            'view mutual matches',
            'manage mutual matches',

            // Reports and audit
            'view office reports',
            'view district reports',
            'export reports',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'Super Admin' => $permissions,

            'Admin' => [
                'view users',
                'manage users',
                'view offices',
                'manage offices',
                'view services',
                'manage services',
                'view eligible employees',
                'import eligible employees',
                'manage eligible employees',
                'view transfer cycles',
                'manage transfer cycles',
                'view district applications',
                'view mutual matches',
                'view district reports',
                'export reports',
            ],

            'Data Entry Officer' => [
                'view offices',
                'view services',
                'view eligible employees',
                'import eligible employees',
                'view transfer cycles',
            ],

            'Employee' => [
                'view offices',
                'view services',
                'view transfer cycles',
                'create own applications',
                'view own applications',
            ],

            'Office Head' => [
                'view offices',
                'view services',
                'view transfer cycles',
                'view office applications',
                'review office applications',
                'view office reports',
                'export reports',
            ],

            'District Approver' => [
                'view offices',
                'view services',
                'view transfer cycles',
                'view district applications',
                'review district applications',
                'view mutual matches',
                'manage mutual matches',
                'view district reports',
                'export reports',
            ],

            'Auditor' => [
                'view offices',
                'view services',
                'view transfer cycles',
                'view district reports',
                'view audit logs',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
