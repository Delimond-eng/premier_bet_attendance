<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function getActions()
    {
        $actions = [];

        foreach ((array) config('actions') as $item) {
            $entity = (string) ($item['entity'] ?? '');
            $entityActions = [];

            foreach ((array) ($item['actions'] ?? []) as $actionName) {
                $actionName = (string) $actionName;
                if ($entity === '' || $actionName === '') {
                    continue;
                }

                $entityActions[] = [
                    'name' => "{$entity}.{$actionName}",
                    'action' => $actionName,
                    'label' => $this->translateAction($actionName),
                    'entity' => $entity,
                ];
            }

            $actions[] = [
                'entity' => $entity,
                'label' => (string) ($item['label'] ?? $entity),
                'actions' => $entityActions,
            ];
        }

        return response()->json($actions);
    }

    private function translateAction(string $action): string
    {
        $map = [
            'view' => 'Voir',
            'create' => 'Creer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'export' => 'Exporter',
            'import' => 'Importer',
        ];

        return $map[$action] ?? ucfirst($action);
    }

    public function createOrUpdateRole(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'permissions' => 'required|array',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            DB::beginTransaction();

            if (!empty($data['role_id'])) {
                $role = Role::findOrFail($data['role_id']);
                $role->update([
                    'name' => $data['name'],
                ]);
            } else {
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                ]);
            }

            $permissionNames = [];
            $isManagerRole = Str::lower((string) ($role->name ?? $data['name'])) === 'manager';
            if ($isManagerRole) {
                $permissionNames = $this->allPermissionNamesFromActions();
            } else {
                foreach ((array) $data['permissions'] as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                    $permissionNames[] = $permission;
                }

                $permissionNames = array_values(array_unique($permissionNames));
            }

            $role->syncPermissions($permissionNames);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'message' => !empty($data['role_id']) ? 'Role mis a jour avec succes' : 'Role cree avec succes',
                'role' => $role,
                'permissions' => $permissionNames,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function getAllRoles()
    {
        $roles = Role::with('permissions')->get();
        return response()->json([
            'status' => 'success',
            'roles' => $roles,
        ]);
    }

    public function getAllUsers()
    {
        $query = User::query()->with(['roles.permissions', 'permissions', 'station']);

        $auth = auth()->user();
        if ($auth && (!method_exists($auth, 'hasRole') || !$auth->hasRole('admin')) && $auth->station_id !== null && $auth->station_id !== '') {
            $query->where('station_id', (int) $auth->station_id);
        }

        return response()->json([
            'status' => 'success',
            'users' => $query->get(),
        ]);
    }

    public function createOrUpdateUser(Request $request)
    {
        try {
            $userId = $request->user_id;
            $managerStationId = $this->managerStationId();

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $userId,
                'password' => $userId ? 'nullable|string|min:6' : 'required|string|min:6',
                'role' => 'required|string|exists:roles,name',
                'station_id' => 'nullable|integer|exists:sites,id|required_unless:role,admin',
                'user_id' => 'nullable|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            DB::beginTransaction();

            if ($userId) {
                $user = User::findOrFail($userId);
                if ($managerStationId !== null && (int) ($user->station_id ?? 0) !== $managerStationId) {
                    DB::rollBack();
                    return response()->json(['errors' => 'Utilisateur hors station du manager.'], 403);
                }

                $updateData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'station_id' => $managerStationId ?? ($data['station_id'] ?? null),
                ];
                if (!empty($data['password'])) {
                    $updateData['password'] = Hash::make($data['password']);
                }
                $user->update($updateData);
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'station_id' => $managerStationId ?? ($data['station_id'] ?? null),
                    'password' => Hash::make($data['password']),
                ]);
            }

            $newRole = (string) $data['role'];
            if (Str::lower($newRole) === 'manager') {
                $this->ensureManagerRoleHasAllPermissions();
            }

            if ($userId) {
                $isAdmin = $user->hasRole('admin');
                if ($isAdmin && $newRole !== 'admin') {
                    $adminCount = User::role('admin')->count();
                    if ($adminCount <= 1) {
                        DB::rollBack();
                        return response()->json([
                            'errors' => 'Impossible de modifier le role: cet utilisateur est le seul administrateur du systeme.',
                        ]);
                    }
                }
            }

            $user->syncRoles([$newRole]);
            $user->role = $newRole;
            $user->station_id = $managerStationId ?? ($data['station_id'] ?? null);
            $user->save();

            if (array_key_exists('permissions', $data)) {
                $permissionNames = [];
                foreach ((array) ($data['permissions'] ?? []) as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    $permissionNames[] = $permission;
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                }

                $user->syncPermissions(array_values(array_unique($permissionNames)));
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            DB::commit();

            return response()->json([
                'message' => $userId ? 'Utilisateur mis a jour avec succes' : 'Utilisateur cree avec succes',
                'user' => $user->load(['roles', 'station']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function attributeAccess(Request $request)
    {
        try {
            $userId = (int) $request->user_id;
            $managerStationId = $this->managerStationId();

            $validated = $request->validate([
                'user_id' => 'required|int|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($userId);
            if ($managerStationId !== null && (int) ($user->station_id ?? 0) !== $managerStationId) {
                DB::rollBack();
                return response()->json(['errors' => 'Utilisateur hors station du manager.'], 403);
            }

            if (array_key_exists('permissions', $validated)) {
                $permissionNames = [];
                foreach ((array) ($validated['permissions'] ?? []) as $permission) {
                    $permission = trim((string) $permission);
                    if ($permission === '') {
                        continue;
                    }

                    $permissionNames[] = $permission;
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                }

                $user->syncPermissions(array_values(array_unique($permissionNames)));
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur mis a jour avec succes',
                'user' => $user->load(['roles', 'permissions', 'station']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    private function allPermissionNamesFromActions(): array
    {
        $modules = config('actions', []);
        $permissionNames = [];

        foreach ((array) $modules as $key => $moduleConfig) {
            if (!is_array($moduleConfig)) {
                continue;
            }

            $entity = trim((string) ($moduleConfig['entity'] ?? $key));
            if ($entity === '') {
                continue;
            }

            foreach ((array) ($moduleConfig['actions'] ?? []) as $action) {
                $action = trim((string) $action);
                if ($action === '') {
                    continue;
                }

                $name = $entity . '.' . $action;
                Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
                $permissionNames[] = $name;
            }
        }

        return array_values(array_unique($permissionNames));
    }

    private function ensureManagerRoleHasAllPermissions(): void
    {
        $permissions = $this->allPermissionNamesFromActions();
        $roleManager = Role::updateOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $roleManager->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function managerStationId(): ?int
    {
        $auth = auth()->user();
        if (!$auth) {
            return null;
        }

        if (method_exists($auth, 'hasRole') && $auth->hasRole('admin')) {
            return null;
        }

        if ($auth->station_id === null || $auth->station_id === '') {
            return null;
        }

        return (int) $auth->station_id;
    }
}
