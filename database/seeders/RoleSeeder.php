<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::get();

        $adminRole = new Role();
        $adminRole->name = 'Super Admin';
        $adminRole->guard_name = 'web';
        $adminRole->save();

        //assing all permissions to Super admin

        if (!is_null($permissions)) {
            foreach ($permissions as $key => $value) {

                $permission = Permission::where('id', $value->id)->first();
                $adminRole->givePermissionTo($permission);
            }
        }

        //create super admin user

        // User Creation
        $user = new User();
        $user->name = 'Super Admin';
        $user->email = 'superadmin@cuonline.com';
        $user->password = bcrypt('password');
        $user->save();

        $user->roles()->attach('1');


        // $DeptIdsfromUserAccount = UserAccount::all()->pluck('Dept_ID')->toArray();
        // $uniqueDeptIds = array_unique($DeptIdsfromUserAccount);

        // $departmentIdsfromDepartment = Department::all()
        //   ->whereIn('Dept_ID', $uniqueDeptIds)
        //   ->pluck('Dept_ID')->toArray();

        //  dd($departmentIdsfromDepartment);

        //$user->departments()->attach($departmentIdsfromDepartment);
    }
}
