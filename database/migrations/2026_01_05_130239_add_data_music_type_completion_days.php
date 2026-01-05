<?php

use App\Models\MusicTypeCompletionDay;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataMusicTypeCompletionDays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = [
            // Original
            [
                'music_type_id' => 1,
                'department_id' => 2,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 1,
                'department_id' => 3,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 1,
                'department_id' => 4,
                'days_before_release' => 50
            ],
            [
                'music_type_id' => 1,
                'department_id' => 5,
                'days_before_release' => 43
            ],
            [
                'music_type_id' => 1,
                'department_id' => 6,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 1,
                'department_id' => 7,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 1,
                'department_id' => 8,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 1,
                'department_id' => 9,
                'days_before_release' => 41
            ],

            // Remix Original
            [
                'music_type_id' => 2,
                'department_id' => 2,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 2,
                'department_id' => 3,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 2,
                'department_id' => 4,
                'days_before_release' => 50
            ],
            [
                'music_type_id' => 2,
                'department_id' => 5,
                'days_before_release' => 43
            ],
            [
                'music_type_id' => 2,
                'department_id' => 6,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 2,
                'department_id' => 7,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 2,
                'department_id' => 8,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 2,
                'department_id' => 9,
                'days_before_release' => 41
            ],

            // Nonstop Longform Official
            [
                'music_type_id' => 3,
                'department_id' => 2,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 3,
                'department_id' => 3,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 3,
                'department_id' => 4,
                'days_before_release' => 50
            ],
            [
                'music_type_id' => 3,
                'department_id' => 5,
                'days_before_release' => 43
            ],
            [
                'music_type_id' => 3,
                'department_id' => 6,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 3,
                'department_id' => 7,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 3,
                'department_id' => 8,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 3,
                'department_id' => 9,
                'days_before_release' => 41
            ],

            // Nonstop Mashup Official
            [
                'music_type_id' => 4,
                'department_id' => 2,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 4,
                'department_id' => 3,
                'days_before_release' => 45
            ],
            [
                'music_type_id' => 4,
                'department_id' => 4,
                'days_before_release' => 50
            ],
            [
                'music_type_id' => 4,
                'department_id' => 5,
                'days_before_release' => 43
            ],
            [
                'music_type_id' => 4,
                'department_id' => 6,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 4,
                'department_id' => 7,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 4,
                'department_id' => 8,
                'days_before_release' => 41
            ],
            [
                'music_type_id' => 4,
                'department_id' => 9,
                'days_before_release' => 41
            ],

            // Cover
            [
                'music_type_id' => 5,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 5,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 5,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 5,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 5,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 5,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 5,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 5,
                'department_id' => 9,
                'days_before_release' => 8
            ],

            // Remix Bootleg
            [
                'music_type_id' => 6,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 6,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 6,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 6,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 6,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 6,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 6,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 6,
                'department_id' => 9,
                'days_before_release' => 8
            ],

            // Nonstop Longform Bootleg
            [
                'music_type_id' => 7,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 7,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 7,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 7,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 7,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 7,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 7,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 7,
                'department_id' => 9,
                'days_before_release' => 8
            ],

            // Nonstop Mashup
            [
                'music_type_id' => 8,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 8,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 8,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 8,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 8,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 8,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 8,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 8,
                'department_id' => 9,
                'days_before_release' => 8
            ],

            // Experiment
            [
                'music_type_id' => 9,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 9,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 9,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 9,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 9,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 9,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 9,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 9,
                'department_id' => 9,
                'days_before_release' => 8
            ],

            // Jingle
            [
                'music_type_id' => 10,
                'department_id' => 2,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 10,
                'department_id' => 3,
                'days_before_release' => 14
            ],
            [
                'music_type_id' => 10,
                'department_id' => 4,
                'days_before_release' => 18
            ],
            [
                'music_type_id' => 10,
                'department_id' => 5,
                'days_before_release' => 12
            ],
            [
                'music_type_id' => 10,
                'department_id' => 6,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 10,
                'department_id' => 7,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 10,
                'department_id' => 8,
                'days_before_release' => 10
            ],
            [
                'music_type_id' => 10,
                'department_id' => 9,
                'days_before_release' => 8
            ],
        ];

        foreach ($data as $item) {
            MusicTypeCompletionDay::create($item);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
