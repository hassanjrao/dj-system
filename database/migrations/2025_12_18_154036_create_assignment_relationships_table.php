<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentRelationshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('child_assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['parent_assignment_id', 'child_assignment_id'], 'assignment_rel_parent_child_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_relationships');
    }
}
