<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $apiGuard = 'api';

        // 1. Create Roles (as before)
        $roleNames = ['Super Admin', 'Faculty', 'Rector', 'Director', 'HOD'];
        foreach ($roleNames as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $apiGuard]);
        }

        // 2. Define and assign permissions for Super Admin (as before)
        $allPermissions = Permission::where('guard_name', $apiGuard)->get();
        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', $apiGuard)->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // 3. Faculty permissions (as before)
        $facultyModuleIds = Module::whereIn('name', ['Student', 'Faculty'])->pluck('id');
        $facultyPermissions = Permission::whereIn('module_id', $facultyModuleIds)
            ->where('guard_name', $apiGuard)
            ->get();
        $facultyRole = Role::where('name', 'Faculty')->where('guard_name', $apiGuard)->first();
        if ($facultyRole) {
            $facultyRole->syncPermissions($facultyPermissions);
        }

        // 4. RECTOR - Specific Permissions
        $rectorRole = Role::where('name', 'Rector')->where('guard_name', $apiGuard)->first();
        if ($rectorRole) {
            // Rector needs 'view faculty' to see "Second Page" (if 'Faculty' module maps to 'SecondPage' subject)
            // AND 'view role' to see the "Roles & Permissions" section / "Roles" page
            $rectorPermissions = Permission::where('guard_name', $apiGuard)
                ->where(function ($query) {
                    // Assuming 'Faculty' module maps to 'SecondPage' subject, and permission is 'view faculty'
                    $query->where('name', 'view faculty') // Or 'show faculty' if that's your permission name
                        // Assuming 'Role' module maps to 'Role' subject for roles page access
                        ->orWhere('name', 'view role'); // Or 'show role'
                })->get();
            $rectorRole->syncPermissions($rectorPermissions);
        }

        // 5. Director, HOD → can keep random permissions for now, or assign specifics later
        $otherRoleNames = ['Director', 'HOD'];
        foreach ($otherRoleNames as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', $apiGuard)->first();
            if ($role && $allPermissions->isNotEmpty()) {
                $randomCount = min($allPermissions->count(), rand(3, 7)); // Fewer random perms
                if ($randomCount > 0) {
                    $role->syncPermissions($allPermissions->random($randomCount));
                }
            }
        }

        // 6. Create Super Admin user and assign role (as before)
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@ums.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('password')]
        );
        $superAdminUser->assignRole('Super Admin'); // Uses User model's default guard 'api'

        // Create a Rector user for testing
        $rectorUser = User::firstOrCreate(
            ['email' => 'rector@ums.com'],
            ['name' => 'Test Rector', 'password' => bcrypt('password')]
        );
        $rectorUser->assignRole('Rector');
    }
}
