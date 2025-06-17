<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
