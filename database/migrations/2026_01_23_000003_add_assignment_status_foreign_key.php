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

        // make assignment_status nullable
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_status')->nullable()->change();
        });

        DB::table('assignments')->update(['assignment_status' => '']);

        // Add composite foreign key for assignment_status
        // This ensures the status code is valid for the assignment's department
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreign(['department_id', 'assignment_status'], 'assignments_dept_status_foreign')
                ->references(['department_id', 'code'])
                ->on('department_statuses')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        // make assignment_status not nullable
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_status')->nullable(false)->change();
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
