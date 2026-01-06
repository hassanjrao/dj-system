<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BackfillDepartmentSequenceNumbersForExistingAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all distinct department IDs
        $departmentIds = DB::table('assignments')
            ->whereNotNull('department_id')
            ->distinct()
            ->pluck('department_id');

        // For each department, assign sequence numbers based on creation order
        foreach ($departmentIds as $departmentId) {
            $assignments = DB::table('assignments')
                ->where('department_id', $departmentId)
                ->orderBy('id') // Order by ID as proxy for creation order
                ->get(['id']);

            $sequence = 1;
            foreach ($assignments as $assignment) {
                DB::table('assignments')
                    ->where('id', $assignment->id)
                    ->update(['department_sequence_number' => $sequence]);
                $sequence++;
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Set all department_sequence_numbers back to null
        DB::table('assignments')->update(['department_sequence_number' => null]);
    }
}
