<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->get();

        $formattedRoles = $roles->map(function ($role) {
            // 1. Role Name
            $roleName = $role->name;

            // 2. Users Count
            $usersCount = $role->users_count;

            // 3. Permissions Details (Mapping from Spatie to Vuexy's structure)
            $permissionsDetails = [];
            $permissionsByModule = $role->permissions->groupBy(function ($permission) {
                $module = Module::find($permission->module_id); // Assumes module_id is on permission
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
                'users_count' => $usersCount, // We send the count
                // NO 'users' array with avatar URLs sent from backend for now
                'details' => [
                    'name' => $roleName,
                    'permissions' => $permissionsDetails,
                ],
            ];
        });

        return response()->json($formattedRoles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
