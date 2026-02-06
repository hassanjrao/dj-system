<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddNoteToMusicKeysAndUpdateData extends Migration
{
    /**
     * Run the migrations.
     * Adds a `note` column and replaces music_keys data with name/note pairs, ordered by name.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('music_keys', function (Blueprint $table) {
            $table->string('note')->nullable()->after('name');
        });

        $rows = [
            ['name' => '1A', 'note' => 'G#m'],
            ['name' => '1B', 'note' => 'B major'],
            ['name' => '2A', 'note' => 'D#m'],
            ['name' => '2B', 'note' => 'F# major'],
            ['name' => '3A', 'note' => 'A#m'],
            ['name' => '3B', 'note' => 'C# major'],
            ['name' => '4A', 'note' => 'Fm'],
            ['name' => '4B', 'note' => 'G# major'],
            ['name' => '5A', 'note' => 'Cm'],
            ['name' => '5B', 'note' => 'D# major'],
            ['name' => '6A', 'note' => 'Gm'],
            ['name' => '6B', 'note' => 'A# major'],
            ['name' => '7A', 'note' => 'Dm'],
            ['name' => '7B', 'note' => 'F# major'],
            ['name' => '8A', 'note' => 'Am'],
            ['name' => '8B', 'note' => 'C major'],
            ['name' => '9A', 'note' => 'Em'],
            ['name' => '9B', 'note' => 'G major'],
            ['name' => '10A', 'note' => 'Bm'],
            ['name' => '10B', 'note' => 'D major'],
            ['name' => '11A', 'note' => 'F#m'],
            ['name' => '11B', 'note' => 'A major'],
            ['name' => '12A', 'note' => 'C#m'],
            ['name' => '12B', 'note' => 'E major'],
        ];

        // Delete existing rows (assignments/songs music_key_id will be set to null via FK onDelete)
        DB::table('music_keys')->delete();

        $now = now();
        foreach ($rows as $row) {
            DB::table('music_keys')->insert([
                'name' => $row['name'],
                'note' => $row['note'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('music_keys', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        // Optionally re-seed original keys if you need to roll back data - run LookupTableSeeder for music keys
    }
}
