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
        Schema::table('activities', function (Blueprint $table): void {
            $table->foreignId('paper_strategy_id')
                ->nullable()
                ->after('enabled')
                ->constrained('paper_strategies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('paper_strategy_id');
        });
    }
};
