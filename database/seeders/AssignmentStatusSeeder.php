<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssignmentStatus;

class AssignmentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            ['code' => 'pending', 'description' => 'Pending'],
            ['code' => 'in-progress', 'description' => 'In Progress'],
            ['code' => 'completed', 'description' => 'Completed'],
            ['code' => 'on-hold', 'description' => 'On Hold'],
        ];

        foreach ($statuses as $status) {
            AssignmentStatus::create($status);
        }
    }
}
