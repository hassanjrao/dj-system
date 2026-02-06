<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMusicTypeCategoryLeadTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_type_category_lead_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('music_type_category_id')->constrained('music_type_categories')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->unsignedInteger('days_before_release');
            $table->timestamps();

            $table->unique(['music_type_category_id', 'department_id'], 'music_type_category_lead_times_unique');
        });

        $categories = DB::table('music_type_categories')->whereIn('name', ['Original', 'Bootleg'])->get()->keyBy('name');
        $departmentSlugs = [
            'music-creation', 'video-filming', 'music-mastering', 'graphic-design',
            'video-editing', 'distribution-video', 'distribution-music', 'marketing',
        ];
        $departments = DB::table('departments')->whereIn('slug', $departmentSlugs)->get()->keyBy('slug');

        $grid = [
            'Original' => [
                'music-creation' => 55, 'video-filming' => 55, 'music-mastering' => 45,
                'graphic-design' => 45, 'video-editing' => 40, 'distribution-video' => 35,
                'distribution-music' => 35, 'marketing' => 35,
            ],
            'Bootleg' => [
                'music-creation' => 25, 'video-filming' => 25, 'music-mastering' => 20,
                'graphic-design' => 20, 'video-editing' => 15, 'distribution-video' => 10,
                'distribution-music' => 10, 'marketing' => 10,
            ],
        ];

        $now = now();
        $rows = [];
        foreach ($grid as $categoryName => $deptDays) {
            $category = $categories->get($categoryName);
            if (!$category) {
                continue;
            }
            foreach ($deptDays as $slug => $days) {
                $dept = $departments->get($slug);
                if (!$dept) {
                    continue;
                }
                $rows[] = [
                    'music_type_category_id' => $category->id,
                    'department_id' => $dept->id,
                    'days_before_release' => $days,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if (!empty($rows)) {
            DB::table('music_type_category_lead_times')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_type_category_lead_times');
    }
}
