<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            ['name' => 'Music Creation', 'slug' => 'music-creation'],
            ['name' => 'Music Mastering', 'slug' => 'music-mastering'],
            ['name' => 'Graphic Design', 'slug' => 'graphic-design'],
            ['name' => 'Video Filming', 'slug' => 'video-filming'],
            ['name' => 'Video Editing', 'slug' => 'video-editing'],
            ['name' => 'Distribution - Video', 'slug' => 'distribution-video'],
            ['name' => 'Distribution - Graphic', 'slug' => 'distribution-graphic'],
            ['name' => 'Distribution - Music', 'slug' => 'distribution-music'],
            ['name' => 'Marketing', 'slug' => 'marketing'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
