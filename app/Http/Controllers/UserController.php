<?php

namespace App\Http\Controllers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function getActions()
    {
        $actions = [];

        foreach (config('actions') as $item) {
            $entity = $item['entity'];
            $entityActions = [];

            foreach ($item['actions'] as $actionName) {
                $entityActions[] = [
                    'name'   => "{$entity}.{$actionName}", // ex: agents.view
                    'action' => $actionName,               // ex: view
                    'label'  => $this->translateAction($actionName),                    // ex: Voir
                    'entity' => $entity                    // ex: agents
                ];
            }

            $actions[] = [
                'entity'  => $entity,
                'label'   => $item['label'],
                'actions' => $entityActions
            ];
        }

        return response()->json($actions);
    }
    /**
     * Traduit le nom d'une action en label français
     */
    private function translateAction(string $action): string
    {
        $map = [
            'view'   => 'Voir',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'export' => 'Exporter',
            'import' => 'Importer',
        ];

        return $map[$action] ?? ucfirst($action); // si action inconnue, on met la première lettre en majuscule
    }


    public function createOrUpdateRole(Request $request)
    {
        try {
            $data = $request->validate([
                'name'        => 'required|string',
                'permissions' => 'required|array',
                'role_id'     => 'nullable|exists:roles,id'
            ]);

            DB::beginTransaction();

            // Vérifie si c'est une mise à jour ou une création
            if (!empty($data['role_id'])) {
                // Update
                $role = Role::findOrFail($data['role_id']);
                $role->update([
                    'name' => $data['name']
                ]);
            } else {
                // Create
                $role = Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web'
                ]);
            }

            // Gestion des permissions
            $permissionNames = [];
            foreach ($data["permissions"] as $permission) {
                $permissionNames[] = $permission;
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
            }
            // Synchroniser les permissions
            $role->syncPermissions($permissionNames);
            DB::commit();
            return response()->json([
                'message' => !empty($data['role_id']) ? 'Rôle mis à jour avec succès' : 'Rôle créé avec succès',
                'role'    => $role,
                'permissions' => $permissionNames
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }



    public function getAllRoles(){
        $roles = Role::with("permissions")->get();
        return response()->json([
            'status' => 'success',
            'roles' => $roles,
        ]);
    }

    public function getAllUsers(){
        $users = User::with(["roles.permissions", "permissions"])->get();
        return response()->json([
            'status' => 'success',
            'users' => $users,
        ]);
    }

    public function createOrUpdateUser(Request $request)
    {
        try {
            $userId = $request->user_id;

            $data = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email,' . $userId,
                'password' => $userId ? 'nullable|string|min:6' : 'required|string|min:6',
                'role'     => 'required|string|exists:roles,name',
                'user_id'  => 'nullable|exists:users,id'
            ]);

            DB::beginTransaction();

            // 🔹 CREATE ou UPDATE
            if ($userId) {
                $user = User::findOrFail($userId);
                $updateData = [
                    'name'  => $data['name'],
                    'email' => $data['email'],
                ];
                if (!empty($data['password'])) {
                    $updateData['password'] = Hash::make($data['password']);
                }
                $user->update($updateData);
            } else {
                $user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'role'    => $data['role'],
                    'password' => Hash::make($data['password']),
                ]);
            }

            $newRole = $data['role'];

            if ($userId) {
                $isAdmin = $user->hasRole('admin');
                if ($isAdmin && $newRole !== 'admin') {
                    $adminCount = User::where('role', 'admin')->count();
                    if ($adminCount <= 1) {
                        DB::rollBack();
                        return response()->json([
                            'errors' => "Impossible de modifier le rôle : cet utilisateur est le seul administrateur du système."
                        ]);
                    }
                }
            }
            // 🔹 Attribution / mise à jour du rôle
            $user->syncRoles([$newRole]);
            DB::commit();

            return response()->json([
                'message' => $userId
                    ? 'Utilisateur mis à jour avec succès'
                    : 'Utilisateur créé avec succès',
                'user' => $user->load('roles')
            ]);
        }catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['errors' => $e->getMessage()]);
        }
    }


    public function attributeAccess(Request $request){
        try{
            $userId = (int) $request->user_id;

            $validated = $request->validate([
                'user_id' => 'required|int|exists:users,id',
                'permissions' => 'nullable|array'
            ]);

            DB::beginTransaction();

            $user = User::findOrFail($userId);

            // 3️⃣ Permissions directes user
            if (!empty($validated['permissions'])) {

                $permissionNames = [];
                foreach ($validated["permissions"] as $permission) {
                    $permissionNames[] = $permission;
                    Permission::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => 'web'
                    ]);
                }
                $user->syncPermissions($permissionNames);
            }
            DB::commit();
            return response()->json([
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => $user->load(['roles', 'permissions'])
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors ]);
        }
        catch (\Illuminate\Database\QueryException $e){
            return response()->json(['errors' => $e->getMessage() ]);
        }
    }
}
