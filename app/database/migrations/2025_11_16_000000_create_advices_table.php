<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advices', function (Blueprint $table) {
            $table->bigIncrements('id');

            // foreignId to projects table, cascade on delete
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            // group and test_field as nullable
            // NOTE: group is intentionally a text field (was string in original PR)
            $table->text('group')->nullable();
            $table->text('test_field')->nullable();

            // content required
            $table->text('content');

            $table->timestamps();

            // indexes
            $table->index('project_id');
            $table->index(['project_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advices');
    }
};
