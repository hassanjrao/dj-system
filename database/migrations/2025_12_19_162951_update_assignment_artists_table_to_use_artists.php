<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentArtistsTableToUseArtists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the foreign key constraint on assignment_id temporarily
        // Laravel creates FK names as: {table}_{column}_foreign
        Schema::table('assignment_artists', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
        });

        // Drop the unique constraint
        \DB::statement('ALTER TABLE assignment_artists DROP INDEX assignment_artists_unique');

        // Add artist_id column
        Schema::table('assignment_artists', function (Blueprint $table) {
            $table->unsignedBigInteger('artist_id')->nullable()->after('assignment_id');
        });

        // Recreate the foreign key on assignment_id
        Schema::table('assignment_artists', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });

        // Add foreign key constraint for artist_id
        Schema::table('assignment_artists', function (Blueprint $table) {
            $table->foreign('artist_id')->references('id')->on('artists')->onDelete('cascade');
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
            $table->dropForeign(['artist_id']);
            $table->dropColumn('artist_id');
        });

        // Restore the old unique constraint using raw SQL
        \DB::statement('ALTER TABLE assignment_artists ADD UNIQUE KEY assignment_artists_unique (assignment_id, artist_name)');
    }
}
