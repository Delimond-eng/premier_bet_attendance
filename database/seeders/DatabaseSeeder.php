<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Database\Seeders\DemoDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // 0) Reset cache Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Charger modules depuis config/actions.php
        $modules = config('actions', []);
        if (!is_array($modules) || empty($modules)) {
            $this->command?->warn("⚠️ config('actions') est vide ou invalide. Vérifie config/actions.php");
            return;
        }

        // 2) Créer toutes les permissions à partir de la config
        // Permission name format: "{action} {entity}"
        $allPermissionNames = [];

        foreach ($modules as $key => $moduleConfig) {
            if (!is_array($moduleConfig)) continue;

            $entity  = $moduleConfig['entity']  ?? $key;
            $actions = $moduleConfig['actions'] ?? [];

            if (!is_string($entity) || trim($entity) === '') continue;
            if (!is_array($actions)) $actions = [];

            foreach ($actions as $action) {
                if (!is_string($action) || trim($action) === '') continue;

                $permissionName = trim($action) . ' ' . trim($entity);
                Permission::updateOrCreate(['name' => $permissionName]);
                $allPermissionNames[] = $permissionName;
            }
        }

        $allPermissionNames = array_values(array_unique($allPermissionNames));

        $roleAdmin      = Role::updateOrCreate(['name' => 'admin']);

        // 4) Attribuer les permissions aux rôles (basé sur la config)

        // 4.1 Super admin: toutes les permissions
        $roleAdmin->syncPermissions($allPermissionNames);

        $adminPermissionNames = [];
        foreach ($modules as $key => $moduleConfig) {
            $entity  = $moduleConfig['entity']  ?? $key;
            $actions = $moduleConfig['actions'] ?? [];

            foreach ((array)$actions as $action) {
                $adminPermissionNames[] = trim($action) . ' ' . trim($entity);
            }
        }
        $roleAdmin->syncPermissions(array_values(array_unique($adminPermissionNames)));

        // Exemple: accès lecture RH + rapports (lecture/export optionnel)
        $supervisorAllowedEntities = [
            'dashboard_admin',
            'agents',
            'stations',
            'horaires',
            'groupes',
            'plannings',
            'retards',
            'absences',
            'conges',
            'rapport_presences',
            'rapport_conges',
            'rapport_retards',
            'logs',
        ];

        // 5) Créer un utilisateur admin par défaut
        $adminUser = User::updateOrCreate(
            ['email' => 'demo@gmail.com'],
            [
                'name' => 'Administrateur SALAMA',
                'password' => Hash::make('demo@2025'),
            ]
        );

        $adminUser->syncRoles([$roleAdmin]);

        // 6) Données de démonstration (stations, agents, plannings, présences, RH)
        $this->call(DemoDataSeeder::class);

        $this->command?->info('✅ Seeder terminé : Permissions générées depuis config(actions), rôles créés, admin créé.');
    }
}
