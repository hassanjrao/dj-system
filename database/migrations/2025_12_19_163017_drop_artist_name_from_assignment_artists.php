<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropArtistNameFromAssignmentArtists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_artists', function (Blueprint $table) {
            // Make artist_id required (not nullable) before dropping artist_name
            $table->foreignId('artist_id')->nullable(false)->change();

            // Drop artist_name column
            $table->dropColumn('artist_name');

            // Add new unique constraint on assignment_id and artist_id
            $table->unique(['assignment_id', 'artist_id'], 'assignment_artists_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_artists', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('assignment_artists_unique');

            // Add back artist_name column
            $table->string('artist_name')->after('assignment_id');

            // Make artist_id nullable again
            $table->foreignId('artist_id')->nullable()->change();

            // Restore old unique constraint
            $table->unique(['assignment_id', 'artist_name'], 'assignment_artists_unique');
        });
    }
}
