<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Department;

class SeedDummyUsersToDepartmentUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all departments
        $departments = Department::all()->keyBy('slug');

        // Get or create dummy users
        $users = [];

        // BOB - belongs to: Music Creation, Music Mastering, Graphic Design, Distribution Video
        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob',
                'password' => bcrypt('password')
            ]
        );
        $bobDepartments = [];
        if (isset($departments['music-creation'])) {
            $bobDepartments[] = $departments['music-creation']->id;
        }
        if (isset($departments['music-mastering'])) {
            $bobDepartments[] = $departments['music-mastering']->id;
        }
        if (isset($departments['graphic-design'])) {
            $bobDepartments[] = $departments['graphic-design']->id;
        }
        if (isset($departments['distribution-video'])) {
            $bobDepartments[] = $departments['distribution-video']->id;
        }
        if (!empty($bobDepartments)) {
            $bob->departments()->sync($bobDepartments);
        }

        // DAVE - belongs to: Music Creation, Music Mastering, Graphic Design, Distribution Video
        $dave = User::firstOrCreate(
            ['email' => 'dave@example.com'],
            [
                'name' => 'Dave',
                'password' => bcrypt('password')
            ]
        );
        $daveDepartments = [];
        if (isset($departments['music-creation'])) {
            $daveDepartments[] = $departments['music-creation']->id;
        }
        if (isset($departments['music-mastering'])) {
            $daveDepartments[] = $departments['music-mastering']->id;
        }
        if (isset($departments['graphic-design'])) {
            $daveDepartments[] = $departments['graphic-design']->id;
        }
        if (isset($departments['distribution-video'])) {
            $daveDepartments[] = $departments['distribution-video']->id;
        }
        if (!empty($daveDepartments)) {
            $dave->departments()->sync($daveDepartments);
        }

        // MAX - belongs to: Graphic Design, Video Filming, Video Editing, Distribution Video
        $max = User::firstOrCreate(
            ['email' => 'max@example.com'],
            [
                'name' => 'Max',
                'password' => bcrypt('password')
            ]
        );
        $maxDepartments = [];
        if (isset($departments['graphic-design'])) {
            $maxDepartments[] = $departments['graphic-design']->id;
        }
        if (isset($departments['video-filming'])) {
            $maxDepartments[] = $departments['video-filming']->id;
        }
        if (isset($departments['video-editing'])) {
            $maxDepartments[] = $departments['video-editing']->id;
        }
        if (isset($departments['distribution-video'])) {
            $maxDepartments[] = $departments['distribution-video']->id;
        }
        if (!empty($maxDepartments)) {
            $max->departments()->sync($maxDepartments);
        }

        // GEORGE - belongs to: Graphic Design, Video Filming, Video Editing
        $george = User::firstOrCreate(
            ['email' => 'george@example.com'],
            [
                'name' => 'George',
                'password' => bcrypt('password')
            ]
        );
        $georgeDepartments = [];
        if (isset($departments['graphic-design'])) {
            $georgeDepartments[] = $departments['graphic-design']->id;
        }
        if (isset($departments['video-filming'])) {
            $georgeDepartments[] = $departments['video-filming']->id;
        }
        if (isset($departments['video-editing'])) {
            $georgeDepartments[] = $departments['video-editing']->id;
        }
        if (!empty($georgeDepartments)) {
            $george->departments()->sync($georgeDepartments);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove department associations for dummy users
        $emails = ['bob@example.com', 'dave@example.com', 'max@example.com', 'george@example.com'];
        $users = User::whereIn('email', $emails)->get();

        foreach ($users as $user) {
            $user->departments()->detach();
        }
    }
}
