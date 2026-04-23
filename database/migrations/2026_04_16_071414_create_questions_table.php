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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('content');
            $table->string('type')->default('single'); // single, multiple, text, etc.
            $table->json('options')->nullable(); // for choice questions
            $table->string('answer')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1); // 1-5
            $table->json('tags')->nullable();
            $table->boolean('status')->default(true); // enabled/disabled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
