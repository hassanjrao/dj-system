<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // DepartmentSeeder::class,
            PermissionSeeder::class,
            // LookupTableSeeder::class,
            // AssignmentStatusSeeder removed - statuses now seeded via migration
            UserSeeder::class,
        ]);
    }
}
