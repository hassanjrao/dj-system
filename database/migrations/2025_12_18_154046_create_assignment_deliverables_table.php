<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentDeliverablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('deliverable_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'completed', 'uploaded'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'deliverable_id'], 'assignment_deliverables_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_deliverables');
    }
}
