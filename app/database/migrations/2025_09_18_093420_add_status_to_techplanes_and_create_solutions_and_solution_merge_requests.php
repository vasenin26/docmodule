<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add execution status to techplanes
        Schema::table('techplanes', function (Blueprint $table) {
            if (!Schema::hasColumn('techplanes', 'status')) {
                $table->string('status')->default('planned')->after('generation_status');
            }
        });

        // Create solutions table
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        // Pivot table between techplanes and solutions
        Schema::create('techplane_solution', function (Blueprint $table) {
            $table->unsignedBigInteger('solution_id');
            $table->unsignedBigInteger('techplane_id');

            $table->foreign('solution_id')->references('id')->on('solutions')->onDelete('cascade');
            $table->foreign('techplane_id')->references('id')->on('techplanes')->onDelete('cascade');

            $table->primary(['solution_id', 'techplane_id']);

            $table->index('solution_id');
            $table->index('techplane_id');
        });

        // Solution merge requests table
        Schema::create('solution_merge_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solution_id');
            $table->string('url');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('solution_id')->references('id')->on('solutions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index('solution_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop dependent tables first
        Schema::dropIfExists('solution_merge_requests');
        Schema::dropIfExists('techplane_solution');
        Schema::dropIfExists('solutions');

        // Remove execution status from techplanes
        Schema::table('techplanes', function (Blueprint $table) {
            if (Schema::hasColumn('techplanes', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
