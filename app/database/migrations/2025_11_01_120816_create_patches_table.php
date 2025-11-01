<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patches', function (Blueprint $table) {
            $table->id();

            $table->string('target');
            $table->string('target_id');

            $table->string('title');
            $table->text('content');

            $table->boolean('accepted')->default(false);
            $table->dateTime('accepted_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patches');
    }
};
