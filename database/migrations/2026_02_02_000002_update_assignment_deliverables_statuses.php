<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateAssignmentDeliverablesStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get the default "pending" status IDs for each type
        $completionPendingId = DB::table('deliverable_statuses')
            ->where('type', 'completion')
            ->where('code', 'pending')
            ->value('id');

        $waveUploadPendingId = DB::table('deliverable_statuses')
            ->where('type', 'wave_upload')
            ->where('code', 'pending')
            ->value('id');

        $mp3UploadPendingId = DB::table('deliverable_statuses')
            ->where('type', 'mp3_upload')
            ->where('code', 'pending')
            ->value('id');

        Schema::table('assignment_deliverables', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('completion_status_id')
                ->nullable()
                ->after('deliverable_id')
                ->constrained('deliverable_statuses')
                ->nullOnDelete();

            $table->foreignId('wave_upload_status_id')
                ->nullable()
                ->after('completion_status_id')
                ->constrained('deliverable_statuses')
                ->nullOnDelete();

            $table->foreignId('mp3_upload_status_id')
                ->nullable()
                ->after('wave_upload_status_id')
                ->constrained('deliverable_statuses')
                ->nullOnDelete();
        });

        // Set default values for existing records
        DB::table('assignment_deliverables')->update([
            'completion_status_id' => $completionPendingId,
            'wave_upload_status_id' => $waveUploadPendingId,
            'mp3_upload_status_id' => $mp3UploadPendingId,
        ]);

        // Drop the old status column
        Schema::table('assignment_deliverables', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_deliverables', function (Blueprint $table) {
            // Re-add the old status column
            $table->enum('status', ['pending', 'completed', 'uploaded'])
                ->default('pending')
                ->after('deliverable_id');
        });

        Schema::table('assignment_deliverables', function (Blueprint $table) {
            // Drop foreign key constraints first, then the columns
            $table->dropForeign(['completion_status_id']);
            $table->dropForeign(['wave_upload_status_id']);
            $table->dropForeign(['mp3_upload_status_id']);

            $table->dropColumn(['completion_status_id', 'wave_upload_status_id', 'mp3_upload_status_id']);
        });
    }
}
