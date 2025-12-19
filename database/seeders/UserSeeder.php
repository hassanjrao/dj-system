<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get departments
        $musicCreation = Department::where('slug', 'music-creation')->first();
        $musicMastering = Department::where('slug', 'music-mastering')->first();
        $graphicDesign = Department::where('slug', 'graphic-design')->first();
        $videoEditing = Department::where('slug', 'video-editing')->first();
        $distribution = Department::where('slug', 'distribution-music')->first();

        // Get roles
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $viewAllEditRole = Role::where('name', 'view-all-edit-assigned')->first();
        $viewAllUpdateRole = Role::where('name', 'view-all-update-assigned')->first();

        // Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password')
            ]
        );
        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
        }

        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password')
            ]
        );
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        // View All & Edit/Create Assigned - Music Mastering
        if ($musicMastering) {
            $user1 = User::firstOrCreate(
                ['email' => 'musicmastering@example.com'],
                [
                    'name' => 'Music Mastering Manager',
                    'password' => Hash::make('password'),
                ]
            );
            if ($viewAllEditRole) {
                $user1->assignRole($viewAllEditRole);
            }
        }

        // View All & Edit/Create Assigned - Graphic Design
        if ($graphicDesign) {
            $user2 = User::firstOrCreate(
                ['email' => 'graphicdesign@example.com'],
                [
                    'name' => 'Graphic Design Manager',
                    'password' => Hash::make('password'),
                ]
            );
            if ($viewAllEditRole) {
                $user2->assignRole($viewAllEditRole);
            }
        }

        // View All & Edit/Create Assigned - Video Editing
        if ($videoEditing) {
            $user3 = User::firstOrCreate(
                ['email' => 'videoediting@example.com'],
                [
                    'name' => 'Video Editing Manager',
                    'password' => Hash::make('password'),
                ]
            );
            if ($viewAllEditRole) {
                $user3->assignRole($viewAllEditRole);
            }
        }

        // View All & Update Assigned - Music Mastering
        if ($musicMastering) {
            $user4 = User::firstOrCreate(
                ['email' => 'musicmastering.update@example.com'],
                [
                    'name' => 'Music Mastering Staff',
                    'password' => Hash::make('password'),
                ]
            );
            if ($viewAllUpdateRole) {
                $user4->assignRole($viewAllUpdateRole);
            }
        }

        // View All & Update Assigned - Distribution
        if ($distribution) {
            $user5 = User::firstOrCreate(
                ['email' => 'distribution@example.com'],
                [
                    'name' => 'Distribution Staff',
                    'password' => Hash::make('password'),
                ]
            );
            if ($viewAllUpdateRole) {
                $user5->assignRole($viewAllUpdateRole);
            }
        }

        // Regular department user (no special role, just assigned to department)
        if ($musicMastering) {
            $user6 = User::firstOrCreate(
                ['email' => 'musicmastering.user@example.com'],
                [
                    'name' => 'Music Mastering User',
                    'password' => Hash::make('password'),
                ]
            );
        }

        // Another regular user for Graphic Design
        if ($graphicDesign) {
            $user7 = User::firstOrCreate(
                ['email' => 'graphicdesign.user@example.com'],
                [
                    'name' => 'Graphic Design User',
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
