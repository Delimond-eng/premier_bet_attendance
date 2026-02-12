<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. DÉFINITION DES PERMISSIONS PAR MODULE
        $modules = [
            'stations'   => ['view', 'create', 'edit', 'delete', 'export'],
            'agents'     => ['view', 'create', 'edit', 'delete', 'import'],
            'presences'  => ['view', 'create', 'edit', 'delete', 'live'],
            'plannings'  => ['view', 'create', 'edit', 'generate'],
            'reports'    => ['view', 'export'],
            'users'      => ['view', 'create', 'edit', 'delete'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::updateOrCreate(['name' => "{$action} {$module}"]);
            }
        }

        // 2. CRÉATION DES RÔLES ET ATTRIBUTION DES PERMISSIONS

        // Super-Admin : Tout est permis
        $roleSuperAdmin = Role::updateOrCreate(['name' => 'Super-Admin']);
        // Super-admin a implicitement tous les accès via Gate::before dans AuthServiceProvider

        // Administrateur RH / Ops
        $roleAdmin = Role::updateOrCreate(['name' => 'Admin']);
        $roleAdmin->givePermissionTo([
            'view stations', 'edit stations',
            'view agents', 'create agents', 'edit agents', 'import agents',
            'view presences', 'live presences',
            'view plannings', 'generate plannings',
            'view reports', 'export reports'
        ]);

        // Superviseur Station
        $roleSupervisor = Role::updateOrCreate(['name' => 'Supervisor']);
        $roleSupervisor->givePermissionTo([
            'view stations',
            'view agents',
            'view presences', 'live presences',
            'view plannings'
        ]);

        // 3. CRÉATION DE L'UTILISATEUR PAR DÉFAUT
        $adminUser = User::updateOrCreate(
            ['email' => 'demo@gmail.com'],
            [
                'name' => 'Administrateur SALAMA',
                'password' => Hash::make('demo@2025'),
            ]
        );

        $adminUser->assignRole($roleSuperAdmin);

        $this->command->info('✅ Seeder terminé : Rôles, Permissions et Admin créés.');
    }
}
