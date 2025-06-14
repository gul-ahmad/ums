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
        // Each user with their assigned role (not a DB column)
        $usersWithRoles = [
            ['name' => 'John Doe', 'email' => 'johndoe@example.com', 'role' => 'Faculty'],
            ['name' => 'Facutly', 'email' => 'faculty@example.com', 'role' => 'Faculty'],
            ['name' => 'Faculty1', 'email' => 'faculty1@example.com', 'role' => 'Faculty'],
            ['name' => 'Faculty2', 'email' => 'facaculty2@example.com', 'role' => 'Faculty'],
            ['name' => 'Faculty3', 'email' => 'facaculty3@example.com', 'role' => 'Faculty'],
            ['name' => 'Faculty4', 'email' => 'facaculty4@example.com', 'role' => 'Faculty'],
            ['name' => 'Jane Smith', 'email' => 'janesmith@example.com', 'role' => 'Rector'],
            ['name' => 'Rector', 'email' => 'rector@example.com', 'role' => 'Rector'],
            ['name' => 'Bob Johnson', 'email' => 'bobjohnson@example.com', 'role' => 'Director'],
            ['name' => 'Test 1', 'email' => 'test1@example.com', 'role' => 'HOD'],
            ['name' => 'Test 2', 'email' => 'test2@example.com'],
            ['name' => 'Test 3', 'email' => 'test3@example.com'],
            ['name' => 'Test 4', 'email' => 'test4@example.com'],
            ['name' => 'Test 5', 'email' => 'test5@example.com'],
        ];

        foreach ($usersWithRoles as $userData) {
            $roleName = $userData['role'] ?? null;
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                ]
            );

            if ($roleName) {
                $user->assignRole($roleName);
            }
        }
    }
}
