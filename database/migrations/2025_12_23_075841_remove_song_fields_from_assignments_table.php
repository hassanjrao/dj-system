<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSongFieldsFromAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['music_type_id']);
            $table->dropForeign(['album_id']);
            $table->dropForeign(['music_key_id']);
            $table->dropForeign(['music_genre_id']);

            // Drop columns
            $table->dropColumn([
                'music_type_id',
                'song_name',
                'version_name',
                'album_id',
                'bpm',
                'music_key_id',
                'music_genre_id',
            ]);
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
            $table->foreignId('music_type_id')->nullable()->constrained('music_types')->onDelete('set null');
            $table->string('song_name')->nullable();
            $table->string('version_name')->nullable();
            $table->foreignId('album_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('bpm')->nullable();
            $table->foreignId('music_key_id')->nullable()->constrained('music_keys')->onDelete('set null');
            $table->foreignId('music_genre_id')->nullable()->constrained('music_genres')->onDelete('set null');
        });
    }
}
