<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_attempt_id')->nullable();
            $table->integer('score')->default(0);
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_certificates');
    }
};
