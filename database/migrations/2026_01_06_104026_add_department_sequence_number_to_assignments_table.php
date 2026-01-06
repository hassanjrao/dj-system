<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentSequenceNumberToAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedInteger('department_sequence_number')->nullable()->after('department_id');

            // Add unique constraint on (department_id, department_sequence_number)
            $table->unique(['department_id', 'department_sequence_number'], 'dept_seq_unique');
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
            $table->dropUnique('dept_seq_unique');
            $table->dropColumn('department_sequence_number');
        });
    }
}
