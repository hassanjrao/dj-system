<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class EmptyAssignmentsArtistsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in order (respecting foreign key dependencies)
        if (Schema::hasTable('assignment_artists')) {
            DB::table('assignment_artists')->truncate();
        }
        if (Schema::hasTable('assignments')) {
            DB::table('assignments')->truncate();
        }
        if (Schema::hasTable('artists')) {
            DB::table('artists')->truncate();
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Nothing to reverse - data is already deleted
    }
}
