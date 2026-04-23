<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_lottery_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_attempt_id')->nullable();
            $table->integer('score')->default(0);
            $table->timestamp('eligible_at')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
        });

        Schema::create('quiz_lottery_draws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_lottery_entry_id');
            $table->string('draw_batch');
            $table->unsignedInteger('winner_position')->default(1);
            $table->timestamp('drawn_at');
            $table->timestamps();

            $table->index(['activity_id', 'draw_batch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_lottery_draws');
        Schema::dropIfExists('quiz_lottery_entries');
    }
};
