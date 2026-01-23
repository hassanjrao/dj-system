<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAssignmentStatusForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::table('assignments')->update(['assignment_status' => NULL]);

        // Add composite foreign key for assignment_status
        // This ensures the status code is valid for the assignment's department
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreign(['department_id', 'assignment_status'], 'assignments_dept_status_foreign')
                ->references(['department_id', 'code'])
                ->on('department_statuses')
                ->onDelete('restrict')
                ->onUpdate('cascade');
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
            $table->dropForeign('assignments_dept_status_foreign');
        });
    }
}
