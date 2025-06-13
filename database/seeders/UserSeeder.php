<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'John Doe', 'email' => 'johndoe@example.com', 'password' => bcrypt('password')],
            ['name' => 'Jane Smith', 'email' => 'janesmith@example.com', 'password' => bcrypt('password')],
            ['name' => 'Bob Johnson', 'email' => 'bobjohnson@example.com', 'password' => bcrypt('password')],
            ['name' => 'Test 1', 'email' => 'test1@example.com', 'password' => bcrypt('password')],
            ['name' => 'Test 2', 'email' => 'test2@example.com', 'password' => bcrypt('password')],
            ['name' => 'Test 3', 'email' => 'test3@example.com', 'password' => bcrypt('password')],
            ['name' => 'Test 4', 'email' => 'test4@example.com', 'password' => bcrypt('password')],
            ['name' => 'Test 5', 'email' => 'test5@example.com', 'password' => bcrypt('password')],
        ];
        foreach ($users as $userData) {
            $user = new User($userData);
            $user->save();
            // Associate the user with one or more departments
            //   $departmentIds = Department::pluck('Dept_ID')->random(rand(1, 3))->toArray();
            //$departmentIds = Department::limit(5)->pluck('Dept_ID')->toArray();
            // $user->departments()->attach($departmentIds);
        }
    }
}
