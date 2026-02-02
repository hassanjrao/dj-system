<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDeliverableStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliverable_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'completion', 'wave_upload', 'mp3_upload'
            $table->string('name'); // Display name: "Pending", "In Progress", etc.
            $table->string('code'); // Programmatic: "pending", "in_progress", etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for efficient querying by type
            $table->index(['type', 'is_active', 'sort_order']);
        });

        // Seed initial data
        $now = now();
        DB::table('deliverable_statuses')->insert([
            // Completion statuses
            [
                'type' => 'completion',
                'name' => 'Pending',
                'code' => 'pending',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'completion',
                'name' => 'In Progress',
                'code' => 'in_progress',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'completion',
                'name' => 'Waiting Approval',
                'code' => 'waiting_approval',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'completion',
                'name' => 'Done',
                'code' => 'done',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Wave upload statuses
            [
                'type' => 'wave_upload',
                'name' => 'Pending',
                'code' => 'pending',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'wave_upload',
                'name' => 'Uploaded',
                'code' => 'uploaded',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // MP3 upload statuses
            [
                'type' => 'mp3_upload',
                'name' => 'Pending',
                'code' => 'pending',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'mp3_upload',
                'name' => 'Uploaded',
                'code' => 'uploaded',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliverable_statuses');
    }
}
