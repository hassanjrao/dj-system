<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameStatusToAssignmentStatusInAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, drop the enum column
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Then add the new column with foreign key
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_status')->nullable()->after('reference_links');
        });

        // Add foreign key constraint separately
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
            $table->dropColumn('assignment_status');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in-progress', 'completed', 'on-hold'])->default('pending')->after('reference_links');
        });
    }
}
