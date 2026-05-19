<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionTag extends Model
{

    protected $fillable = [
        'name',
    ];

    /**
     * @param  array<int, mixed>  $names
     * @return array<int, string>
     */
    public static function normalizeNames(array $names): array
    {
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $names
     */
    public static function syncNames(array $names): void
    {
        $normalizedNames = static::normalizeNames($names);

        if ($normalizedNames === []) {
            return;
        }

        $existingNames = static::query()
            ->whereIn('name', $normalizedNames)
            ->pluck('name')
            ->all();

        $missingNames = array_values(array_diff($normalizedNames, $existingNames));

        if ($missingNames === []) {
            return;
        }

        $timestamp = now();

        static::query()->insertOrIgnore(
            array_map(fn (string $name): array => [
                'name' => $name,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $missingNames),
        );
    }
}

