<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module = [
            [
                'name' => 'Module'

            ],
            [
                'name' => 'Permission'

            ],
            [
                'name' => 'Role'

            ],
            [
                'name' => 'User'

            ],
            [
                'name' => 'Student'

            ],
            [
                'name' => 'Faculty DARC'

            ],
            [
                'name' => 'Faculty'

            ],


        ];

        foreach ($module as $item) {

            Module::create($item);
        }
    }
}
