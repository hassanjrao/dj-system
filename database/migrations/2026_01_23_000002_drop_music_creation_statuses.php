<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropMusicCreationStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop foreign key and column from assignments table
        Schema::table('assignments', function (Blueprint $table) {
            // Drop foreign key if exists
            $table->dropForeign(['music_creation_status_id']);


            // Drop column if exists
            if (Schema::hasColumn('assignments', 'music_creation_status_id')) {
                $table->dropColumn('music_creation_status_id');
            }
        });

        // Drop music_creation_statuses table
        Schema::dropIfExists('music_creation_statuses');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate music_creation_statuses table
        Schema::create('music_creation_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add back the column to assignments
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('music_creation_status_id')
                ->nullable()
                ->constrained('music_creation_statuses')
                ->onDelete('set null');
        });
    }
}
