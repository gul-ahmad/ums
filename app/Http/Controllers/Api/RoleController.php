<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        $formattedRoles = $roles->map(function ($role) {
            // Manually count users assigned to this role via model_has_roles
            $usersCount = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_type', config('auth.providers.users.model')) // usually App\Models\User
                ->count();

            $roleName = $role->name;

            $permissionsDetails = [];
            $permissionsByModule = $role->permissions->groupBy(function ($permission) {
                $module = \App\Models\Module::find($permission->module_id); // Caching would be better for perf
                return $module ? $module->name : 'General';
            });

            foreach ($permissionsByModule as $moduleName => $permissionsInModule) {
                $modulePermissionDetails = [
                    'name' => $moduleName,
                    'read' => false,
                    'write' => false,
                    'create' => false,
                ];

                foreach ($permissionsInModule as $permission) {
                    if (str_starts_with($permission->name, 'show') || str_starts_with($permission->name, 'view')) {
                        $modulePermissionDetails['read'] = true;
                    }
                    if (str_starts_with($permission->name, 'edit')) {
                        $modulePermissionDetails['write'] = true;
                    }
                    if (str_starts_with($permission->name, 'create')) {
                        $modulePermissionDetails['create'] = true;
                    }
                }

                $permissionsDetails[] = $modulePermissionDetails;
            }

            return [
                'id' => $role->id,
                'role' => $roleName,
                'users_count' => $usersCount,
                'details' => [
                    'name' => $roleName,
                    'permissions' => $permissionsDetails,
                ],
            ];
        });

        return response()->json([
            'data' => $formattedRoles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id', // Ensures IDs are valid
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'api']); // Ensure guard_name is set

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }
        // Return the newly created role in a consistent format
        return response()->json([
            'message' => 'Role "' . $role->name . '" created successfully!', // Clear success message
            'role' => $this->formatRoleForResponse($role->load('permissions')) // Return formatted role
        ], 201); // HTTP 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $role->load('permissions:id');

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'assigned_permission_ids' => $role->permissions->pluck('id')->toArray(), // Array of IDs [1, 5, 10]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->name = $validated['name'];
        $originalName = $role->name; // For the message
        // $role->guard_name = 'api'; // Should not change usually, but ensure it's set if it could be missing
        $role->save();

        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'message' => 'Role "' . $originalName . '" updated successfully to "' . $role->name . '"!', // Clear success message
            'role' => $this->formatRoleForResponse($role->load('permissions'))
        ]); // HTTP 200 OK (default)
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // RoleController.php
    public function getAllPermissions()
    {
        // Your existing seeder for permissions has module_id
        $permissions = Permission::with('module:id,name') // Eager load module name
            ->get()
            // Group by module name. If a permission has no module, group it under 'General'.
            ->groupBy(function ($permission) {
                return $permission->module ? $permission->module->name : 'General';
            })
            ->map(function ($modulePermissions, $moduleName) {
                return [
                    'moduleName' => $moduleName,
                    'permissions' => $modulePermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name, // e.g., "show user", "edit article"
                            // You might want a "display_name" or parse the action from the name here
                            // For example: 'displayName' => Str::title(str_replace('_', ' ', $permission->name))
                        ];
                    })->values(), // Use values() to reset keys to a simple array
                ];
            })->values(); // Use values() to reset keys for the outer collection

        return response()->json($permissions);
    }

    private function formatRoleForResponse(Role $role)
    {
        // ... (from previous example: formats role name, users_count, details.permissions summary)
        // For store/update, you might just need to return the basic role with its new permissions
        $role->loadCount('users'); // Get fresh user count
        return [
            'id' => $role->id,
            'role' => $role->name, // 'role' key to match what RoleCards.vue expects
            'users_count' => $role->users_count,
            'details' => [ // Reconstruct details if RoleCards.vue reuses this directly
                'name' => $role->name,
                'permissions' => $this->formatPermissionsForDisplay($role) // New helper
            ],
            'assigned_permission_ids' => $role->permissions->pluck('id')->toArray() // Good for verification
        ];
    }

    private function formatPermissionsForDisplay(Role $role)
    {
        $permissionsDetails = [];
        $permissionsByModule = $role->permissions->groupBy(function ($permission) {
            $module = \App\Models\Module::find($permission->module_id);
            return $module ? $module->name : 'General';
        });

        foreach ($permissionsByModule as $moduleName => $permissionsInModule) {
            $modulePermissionDetails = [
                'name' => $moduleName,
                'read' => $permissionsInModule->contains(fn($p) => str_starts_with($p->name, 'show') || str_starts_with($p->name, 'view')),
                'write' => $permissionsInModule->contains(fn($p) => str_starts_with($p->name, 'edit')),
                'create' => $permissionsInModule->contains(fn($p) => str_starts_with($p->name, 'create')),
            ];
            $permissionsDetails[] = $modulePermissionDetails;
        }
        return $permissionsDetails;
    }
}
