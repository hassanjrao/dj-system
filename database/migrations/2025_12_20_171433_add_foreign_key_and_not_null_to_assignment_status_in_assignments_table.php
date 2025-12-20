<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddForeignKeyAndNotNullToAssignmentStatusInAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, update any NULL values to a default status (pending)
        $defaultStatus = DB::table('assignment_statuses')->where('code', 'pending')->first();
        if ($defaultStatus) {
            DB::table('assignments')
                ->whereNull('assignment_status')
                ->update(['assignment_status' => $defaultStatus->code]);
        }

        // Drop the foreign key if it exists
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['assignment_status']);
        });

        // Modify the column to be NOT NULL
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_status')->nullable(false)->change();
        });

        // Re-add the foreign key constraint
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreign('assignment_status')->references('code')->on('assignment_statuses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['assignment_status']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_status')->nullable()->change();
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->foreign('assignment_status')->references('code')->on('assignment_statuses')->onDelete('restrict');
        });
    }
}
