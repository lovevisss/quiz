<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $names = [];

        if (Schema::hasTable('questions')) {
            DB::table('questions')
                ->select('tags')
                ->orderBy('id')
                ->lazy()
                ->each(function (object $row) use (&$names): void {
                    $decoded = json_decode((string) ($row->tags ?? '[]'), true);

                    if (! is_array($decoded)) {
                        return;
                    }

                    foreach ($decoded as $tag) {
                        $name = trim((string) $tag);

                        if ($name !== '') {
                            $names[] = $name;
                        }
                    }
                });
        }

        $normalizedNames = collect($names)
            ->unique()
            ->values();

        if ($normalizedNames->isEmpty()) {
            return;
        }

        $timestamp = now();

        DB::table('question_tags')->insertOrIgnore(
            $normalizedNames
                ->map(fn (string $name): array => [
                    'name' => $name,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all(),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_tags');
    }
};

