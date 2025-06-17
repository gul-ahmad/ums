<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = Module::all();

        // Define permissions for each module
        $permissions = [];

        foreach ($modules as $module) {
            $permissions[] = [
                'name' => 'show ' . strtolower($module->name),
                'guard_name' => 'api',
                'module_id' => $module->id
            ];
            $permissions[] = [
                'name' => 'create ' . strtolower($module->name),
                'guard_name' => 'api',
                'module_id' => $module->id
            ];
            $permissions[] = [
                'name' => 'edit ' . strtolower($module->name),
                'guard_name' => 'api',
                'module_id' => $module->id
            ];
            $permissions[] = [
                'name' => 'delete ' . strtolower($module->name),
                'guard_name' => 'api',
                'module_id' => $module->id
            ];
            $permissions[] = [
                'name' => 'view ' . strtolower($module->name),
                'guard_name' => 'api',
                'module_id' => $module->id
            ];
        }

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
