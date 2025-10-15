<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_generation_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('model_id')->constrained('generation_models')->cascadeOnDelete();
            $table->string('generation_type');
            $table->timestamps();

            $table->unique(['project_id', 'generation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_generation_models');
    }
};


