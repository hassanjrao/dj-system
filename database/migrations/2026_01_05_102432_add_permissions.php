<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = [
            'manage-users',
            'create-assignments',
            'edit-assignments',
            'edit-all-assignments',
            'view-assignments',
            'view-all-assignments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo($permissions);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'create-assignments',
            'edit-assignments',
            'view-assignments',
            'edit-all-assignments',
            'view-all-assignments',
        ]);

        $viewOnlyRole = Role::firstOrCreate(['name' => 'view-only']);
        $viewOnlyRole->givePermissionTo([
            'view-assignments',
            'view-all-assignments',
        ]);

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
