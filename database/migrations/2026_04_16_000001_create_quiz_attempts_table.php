<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->integer('score')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->json('anti_cheat_flags')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};