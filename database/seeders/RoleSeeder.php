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
        // 1. Create Roles
        $roles = ['Super Admin', 'Faculty', 'Rector', 'Director', 'HOD'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
            ]);
        }

        // 2. Assign all permissions to Super Admin
        $allPermissions = Permission::all();
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $superAdmin->syncPermissions($allPermissions);

        // 3. Faculty → permissions related to Student and Faculty modules (by module_id)
        $facultyModuleIds = Module::whereIn('name', ['Student', 'Faculty'])->pluck('id');
        $facultyPermissions = Permission::whereIn('module_id', $facultyModuleIds)->get();

        $faculty = Role::where('name', 'Faculty')->first();
        $faculty->syncPermissions($facultyPermissions);

        // 4. Rector, Director, HOD → random permissions
        $randomRoles = ['Rector', 'Director', 'HOD'];
        foreach ($randomRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $randomPermissions = $allPermissions->random(rand(5, 15));
            $role->syncPermissions($randomPermissions);
        }

        // 5. Create Super Admin user if not exists and assign role
        $user = User::firstOrCreate(
            ['email' => 'superadmin@ums.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('password')]
        );
        $user->assignRole('Super Admin');
    }
}
