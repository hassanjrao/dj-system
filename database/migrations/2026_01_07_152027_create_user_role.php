<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class CreateUserRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // delete view-only role and its permissions
        $viewOnlyRole = Role::where('name', 'view-only')->first();
        $viewOnlyRole->delete();
        $viewOnlyRole->permissions()->delete();

        $userRole->givePermissionTo([
            'create-assignments',
            'view-assignments',
            'edit-assignments',
        ]);
        
        // clear cache
        Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_role');
    }
}
