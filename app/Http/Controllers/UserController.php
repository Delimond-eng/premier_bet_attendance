<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use App\Support\ManagerStationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $stationIds = ManagerStationContext::stationIds();

        // On récupère aussi les préfixes de matricules pour le formulaire utilisateur
        $prefixes = Agent::withoutGlobalScopes()
            ->when($stationIds !== null, fn ($query) => $query->whereIn('site_id', $stationIds))
            ->whereNotNull('matricule')
            ->get(['matricule'])
            ->map(function ($a) {
                $m = trim((string) $a->matricule);
                if (str_contains($m, '-')) {
                    return explode('-', $m)[0];
                }
                if (preg_match('/^[A-Za-z]+/', $m, $matches)) {
                    return strtoupper($matches[0]);
                }
                return strtoupper(substr($m, 0, 2));
            })
            ->unique()
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'roles' => $roles,
            'prefixes' => $prefixes,
        ]);
    }

    public function getAllUsers()
    {
        $authStationIds = ManagerStationContext::stationIds();
        $users = User::query()
            ->with(['roles.permissions', 'permissions', 'station', 'stations', 'region', 'city'])
            ->get()
            ->when($authStationIds !== null, function ($users) use ($authStationIds) {
                return $users->filter(fn (User $user) => $this->userIsWithinStationScope($user, $authStationIds));
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'users' => $users,
        ]);
    }

    public function createOrUpdateUser(Request $request)
    {
        try {
            $userId = $request->user_id;
            $managerStationIds = ManagerStationContext::stationIds();

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $userId,
                'password' => $userId ? 'nullable|string|min:6' : 'required|string|min:6',
                'role' => 'required|string|exists:roles,name',
                'station_id' => 'nullable|integer|exists:sites,id',
                'station_ids' => 'nullable|array',
                'station_ids.*' => 'integer|distinct|exists:sites,id',
                'region_id' => 'nullable|integer|exists:regions,id',
                'city_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('cities', 'id')->where(fn ($query) => $query->where('region_id', $request->input('region_id'))),
                ],
                'matricule_prefix' => 'nullable|string',
                'user_id' => 'nullable|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            $stationIds = array_values(array_unique(array_map(
                'intval',
                $data['station_ids'] ?? (isset($data['station_id']) ? [$data['station_id']] : [])
            )));
            $regionId = $data['region_id'] ?? null;
            $cityId = $data['city_id'] ?? null;
            if (Str::lower((string) $data['role']) === 'admin') {
                $stationIds = [];
                $regionId = null;
                $cityId = null;
            }

            if ($stationIds) {
                $matchingStations = DB::table('sites')
                    ->whereIn('id', $stationIds)
                    ->when($regionId !== null, fn ($query) => $query->where('region_id', $regionId))
                    ->when($cityId !== null, fn ($query) => $query->where('city_id', $cityId))
                    ->count();
                if ($matchingStations !== count($stationIds)) {
                    return response()->json(['errors' => ['Une ou plusieurs stations ne correspondent pas à la province ou à la cité sélectionnée.']], 422);
                }
            }

            $effectiveStationIds = $this->effectiveAssignmentStationIds($stationIds, $regionId, $cityId);

            if (!$this->assignmentIsWithinStationScope($effectiveStationIds, $managerStationIds)) {
                return response()->json(['errors' => ['Les affectations dépassent votre périmètre géographique.']], 403);
            }

            DB::beginTransaction();

            if ($userId) {
                $user = User::findOrFail($userId);
                if (!$this->userIsWithinStationScope($user, $managerStationIds)) {
                    DB::rollBack();
                    return response()->json(['errors' => 'Utilisateur hors du périmètre de l’utilisateur connecté.'], 403);
                }

                $updateData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'station_id' => count($stationIds) === 1 ? $stationIds[0] : null,
                    'region_id' => $regionId,
                    'city_id' => $cityId,
                    'matricule_prefix' => $data['matricule_prefix'] ?? null,
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
                    'station_id' => count($stationIds) === 1 ? $stationIds[0] : null,
                    'region_id' => $regionId,
                    'city_id' => $cityId,
                    'matricule_prefix' => $data['matricule_prefix'] ?? null,
                    'password' => Hash::make($data['password']),
                ]);
            }

            if (array_key_exists('station_ids', $data) || array_key_exists('station_id', $data)) {
                $user->stations()->sync($stationIds);
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
            $user->station_id = count($stationIds) === 1 ? $stationIds[0] : null;
            $user->region_id = $regionId;
            $user->city_id = $cityId;
            $user->matricule_prefix = $data['matricule_prefix'] ?? null;
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
                'user' => $user->load(['roles', 'station', 'stations', 'region', 'city']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function deleteUser(Request $request)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $user = User::findOrFail($data['user_id']);
            if (!$this->userIsWithinStationScope($user, ManagerStationContext::stationIds())) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Utilisateur hors du périmètre de l’utilisateur connecté.'],
                ], 403);
            }

            // Vérifier si l'utilisateur est un admin
            if ($user->hasRole('admin')) {
                $adminCount = User::role('admin')->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => ['Impossible de supprimer le dernier administrateur du système.'],
                    ], 200);
                }
            }

            // Empêcher de se supprimer soi-même (optionnel mais recommandé)
            if ($user->id === auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Vous ne pouvez pas supprimer votre propre compte.'],
                ], 200);
            }

            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Utilisateur supprimé avec succès.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'errors' => [$e->getMessage()]], 200);
        }
    }

    public function attributeAccess(Request $request)
    {
        try {
            $userId = (int) $request->user_id;
            $managerStationIds = ManagerStationContext::stationIds();

            $validated = $request->validate([
                'user_id' => 'required|int|exists:users,id',
                'permissions' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($userId);
            if (!$this->userIsWithinStationScope($user, $managerStationIds)) {
                DB::rollBack();
                return response()->json(['errors' => 'Utilisateur hors du périmètre de l’utilisateur connecté.'], 403);
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

    private function effectiveAssignmentStationIds(array $stationIds, ?int $regionId, ?int $cityId): ?array
    {
        if (!$stationIds && $regionId === null && $cityId === null) {
            return null;
        }

        $query = DB::table('sites')->select('id');
        if ($stationIds) {
            $query->whereIn('id', $stationIds);
        }
        if ($regionId !== null) {
            $query->where('region_id', $regionId);
        }
        if ($cityId !== null) {
            $query->where('city_id', $cityId);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function assignmentIsWithinStationScope(?array $assignmentStationIds, ?array $allowedStationIds): bool
    {
        if ($allowedStationIds === null) {
            return true;
        }

        return $assignmentStationIds !== null
            && array_diff($assignmentStationIds, $allowedStationIds) === [];
    }

    private function userIsWithinStationScope(User $user, ?array $allowedStationIds): bool
    {
        if ($allowedStationIds === null) {
            return true;
        }

        $stationIds = DB::table('user_station')
            ->where('user_id', $user->id)
            ->pluck('station_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (!$stationIds && $user->station_id !== null) {
            $stationIds = [(int) $user->station_id];
        }

        $userStationIds = $this->effectiveAssignmentStationIds(
            $stationIds,
            $user->region_id !== null ? (int) $user->region_id : null,
            $user->city_id !== null ? (int) $user->city_id : null
        );

        return $userStationIds !== null
            && $this->assignmentIsWithinStationScope($userStationIds, $allowedStationIds);
    }
}
