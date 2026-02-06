<?php

use App\Models\MusicType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMusicTypeCategoryIdToMusicTypesTable extends Migration
{
    /**
     * Run the migrations.
     * Maps existing music types to categories: Original vs Bootleg.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('music_types', function (Blueprint $table) {
            $table->foreignId('music_type_category_id')
                ->nullable()
                ->after('id')
                ->constrained('music_type_categories')
                ->onDelete('set null');
        });

        $originalId = DB::table('music_type_categories')->where('name', 'Original')->value('id');
        $bootlegId = DB::table('music_type_categories')->where('name', 'Bootleg')->value('id');

        // truncate music_types table
        // remove foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('music_types')->truncate();
        // re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $mapToOriginal = [
            'Original',
            'Remix Offical',
            'Nonstop Longform Official',
            'Nonstop Mashup Official',
            'Cover Official',
            'Experimental Official',
            'Jingle Official',
        ];

        $mapToBootleg = [
            'Cover Bootleg',
            'Remix Bootleg',
            'Nonstop Longform Bootleg',
            'Experiment Bootleg',
            'Nonstop Mashup Bootleg',
            'Jingle Bootleg',
        ];

        foreach ($mapToOriginal as $name) {
            MusicType::create(['name' => $name, 'music_type_category_id' => $originalId]);
        }
        foreach ($mapToBootleg as $name) {
            MusicType::create(['name' => $name, 'music_type_category_id' => $bootlegId]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('music_types', function (Blueprint $table) {
            $table->dropForeign(['music_type_category_id']);
        });
    }
}
