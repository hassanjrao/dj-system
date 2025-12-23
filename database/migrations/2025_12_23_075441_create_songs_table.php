<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version')->nullable();
            $table->foreignId('album_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('music_type_id')->nullable()->constrained('music_types')->onDelete('set null');
            $table->foreignId('music_genre_id')->nullable()->constrained('music_genres')->onDelete('set null');
            $table->integer('bpm')->nullable();
            $table->foreignId('music_key_id')->nullable()->constrained('music_keys')->onDelete('set null');
            $table->date('release_date')->nullable();
            $table->date('completion_date')->nullable();
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
        Schema::dropIfExists('songs');
    }
}
