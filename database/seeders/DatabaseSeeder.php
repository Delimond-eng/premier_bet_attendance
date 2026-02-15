<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset Spatie cache before seeding.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = config('actions', []);
        if (!is_array($modules) || empty($modules)) {
            $this->command?->warn("config('actions') is empty/invalid. Check config/actions.php");
            return;
        }

        // Create permissions from config/actions.php
        // Permission name format: "{entity}.{action}"
        $permissionNames = [];

        foreach ($modules as $key => $moduleConfig) {
            if (!is_array($moduleConfig)) {
                continue;
            }

            $entity = $moduleConfig['entity'] ?? $key;
            $actions = $moduleConfig['actions'] ?? [];

            if (!is_string($entity) || trim($entity) === '') {
                continue;
            }
            if (!is_array($actions)) {
                $actions = [];
            }

            foreach ($actions as $action) {
                if (!is_string($action) || trim($action) === '') {
                    continue;
                }

                $name = trim($entity) . '.' . trim($action);
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    []
                );
                $permissionNames[] = $name;
            }
        }

        $permissionNames = array_values(array_unique($permissionNames));

        // Default admin role
        $roleAdmin = Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions($permissionNames);

        // Default admin user (demo)
        $adminUser = User::updateOrCreate(
            ['email' => 'demo@gmail.com'],
            [
                'name' => 'Administrateur SALAMA',
                'password' => Hash::make('demo@2025'),
                'role' => 'admin', // keep legacy column in sync
            ]
        );
        $adminUser->syncRoles([$roleAdmin]);

        // Demo data
        $this->call(DemoDataSeeder::class);

        $this->command?->info('Seeder done: permissions/roles created from config(actions), admin user created.');
    }
}

