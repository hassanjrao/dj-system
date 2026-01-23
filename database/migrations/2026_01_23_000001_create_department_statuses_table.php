<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDepartmentStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the department_statuses table if it already exists (from a failed migration)
        Schema::dropIfExists('department_statuses');

        // Create department_statuses table
        Schema::create('department_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'code']);
        });

        // Seed initial statuses
        $departments = DB::table('departments')->pluck('id', 'slug')->toArray();

        // Music Creation specific statuses
        $musicCreationStatuses = [
            ['code' => 'concept', 'name' => 'Concept', 'is_completed' => false, 'is_default' => true, 'sort_order' => 1, 'color' => 'grey'],
            ['code' => 'waiting-lyrics', 'name' => 'Waiting on Lyrics', 'is_completed' => false, 'is_default' => false, 'sort_order' => 2, 'color' => 'orange'],
            ['code' => 'waiting-vocals', 'name' => 'Waiting on Vocals', 'is_completed' => false, 'is_default' => false, 'sort_order' => 3, 'color' => 'amber'],
            ['code' => 'in-production', 'name' => 'In Production', 'is_completed' => false, 'is_default' => false, 'sort_order' => 4, 'color' => 'blue'],
            ['code' => 'done', 'name' => 'Done', 'is_completed' => true, 'is_default' => false, 'sort_order' => 5, 'color' => 'green'],
        ];

        // Standard statuses for other departments
        $standardStatuses = [
            ['code' => 'pending', 'name' => 'Pending', 'is_completed' => false, 'is_default' => true, 'sort_order' => 1, 'color' => 'grey'],
            ['code' => 'in-progress', 'name' => 'In Progress', 'is_completed' => false, 'is_default' => false, 'sort_order' => 2, 'color' => 'blue'],
            ['code' => 'completed', 'name' => 'Completed', 'is_completed' => true, 'is_default' => false, 'sort_order' => 3, 'color' => 'green'],
        ];

        // Departments that use standard statuses
        $standardDepartments = [
            'music-mastering',
            'graphic-design',
            'video-filming',
            'video-editing',
            'distribution-video',
            'distribution-graphic',
            'distribution-music',
            'marketing',
        ];

        $now = now();

        // Insert Music Creation statuses
        if (isset($departments['music-creation'])) {
            foreach ($musicCreationStatuses as $status) {
                DB::table('department_statuses')->insert([
                    'department_id' => $departments['music-creation'],
                    'code' => $status['code'],
                    'name' => $status['name'],
                    'is_completed' => $status['is_completed'],
                    'is_default' => $status['is_default'],
                    'sort_order' => $status['sort_order'],
                    'color' => $status['color'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Insert standard statuses for other departments
        foreach ($standardDepartments as $deptSlug) {
            if (isset($departments[$deptSlug])) {
                foreach ($standardStatuses as $status) {
                    DB::table('department_statuses')->insert([
                        'department_id' => $departments[$deptSlug],
                        'code' => $status['code'],
                        'name' => $status['name'],
                        'is_completed' => $status['is_completed'],
                        'is_default' => $status['is_default'],
                        'sort_order' => $status['sort_order'],
                        'color' => $status['color'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Drop foreign key constraint from assignments table (if exists)
        if (Schema::hasColumn('assignments', 'assignment_status')) {
            // Check if foreign key exists and drop it
                Schema::table('assignments', function (Blueprint $table) {
                    $table->dropForeign(['assignment_status']);
                });

        }

        // Drop old assignment_statuses table
        Schema::dropIfExists('assignment_statuses');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_statuses');

        // Recreate old assignment_statuses table for rollback
        Schema::create('assignment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->timestamps();
        });
    }
}
