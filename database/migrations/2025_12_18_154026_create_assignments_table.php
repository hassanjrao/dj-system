<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assignment_name')->nullable(); // For standalone assignments
            
            // Common fields
            $table->date('completion_date')->nullable();
            $table->date('release_date')->nullable();
            $table->enum('release_timing', ['pre-release', 'post-release', 'other'])->nullable();
            $table->text('notes_for_team')->nullable();
            $table->text('reference_links')->nullable();
            $table->text('notes_for_admin')->nullable(); // Private admin notes
            $table->enum('status', ['pending', 'in-progress', 'completed', 'on-hold'])->default('pending');
            
            // Music Creation specific fields
            $table->foreignId('music_type_id')->nullable()->constrained('music_types')->onDelete('set null');
            $table->string('song_name')->nullable();
            $table->string('version_name')->nullable();
            $table->foreignId('album_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('bpm')->nullable();
            $table->foreignId('music_key_id')->nullable()->constrained('music_keys')->onDelete('set null');
            $table->foreignId('music_genre_id')->nullable()->constrained('music_genres')->onDelete('set null');
            $table->foreignId('music_creation_status_id')->nullable()->constrained('music_creation_statuses')->onDelete('set null');
            
            // Video Editing specific fields
            $table->foreignId('edit_type_id')->nullable()->constrained('edit_types')->onDelete('set null');
            $table->foreignId('footage_type_id')->nullable()->constrained('footage_types')->onDelete('set null');
            
            // Link to parent assignment (for child assignments)
            $table->foreignId('parent_assignment_id')->nullable()->constrained('assignments')->onDelete('cascade');
            $table->foreignId('linked_song_assignment_id')->nullable()->constrained('assignments')->onDelete('set null'); // For Music Mastering to link to Music Creation
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignments');
    }
}
