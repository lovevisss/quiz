<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_endpoint_returns_sorted_results()
    {
        $user = \App\Models\User::factory()->create();
        $activity = \App\Models\Activity::factory()->create();
        $attempts = collect([
            \App\Models\QuizAttempt::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'score' => 100,
                'duration_seconds' => 120,
                'submitted_at' => now()->subMinutes(3)->toDateTimeString(),
            ]),
            \App\Models\QuizAttempt::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id + 1,
                'score' => 90,
                'duration_seconds' => 110,
                'submitted_at' => now()->subMinutes(2)->toDateTimeString(),
            ]),
            \App\Models\QuizAttempt::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id + 2,
                'score' => 90,
                'duration_seconds' => 110,
                'submitted_at' => now()->subMinutes(1)->toDateTimeString(),
            ]),
        ]);

        $this->actingAs($user, 'api');
        $response = $this->getJson("/api/quiz/activities/{$activity->id}/leaderboard");

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['user_id', 'score', 'duration_seconds', 'submitted_at', 'rank']
        ]);

        $responseData = $response->json();

        // Build expected sorted attempts and ranks
        $sorted = $attempts->sortBy([
            ['score', 'desc'],
            ['duration_seconds', 'asc'],
            ['submitted_at', 'asc'],
        ])->values();
        $expected = [];
        $rank = 1;
        $prev = null;
        $sameRankCount = 0;
        foreach ($sorted as $i => $attempt) {
            $current = [
                'score' => $attempt->score,
                'duration_seconds' => $attempt->duration_seconds,
                'submitted_at' => $attempt->submitted_at,
            ];
            if ($prev && $current['score'] === $prev['score'] && $current['duration_seconds'] === $prev['duration_seconds'] && $current['submitted_at'] === $prev['submitted_at']) {
                $sameRankCount++;
            } else {
                $rank += $sameRankCount;
                $sameRankCount = 1;
            }
            $expected[] = [
                'user_id' => $attempt->user_id,
                'score' => $attempt->score,
                'duration_seconds' => $attempt->duration_seconds,
                'submitted_at' => $attempt->submitted_at,
                'rank' => $rank,
            ];
            $prev = $current;
        }

        $this->assertEquals($expected, $responseData);
    }

    public function test_leaderboard_cache_behavior()
    {
        $user = \App\Models\User::factory()->create();
        $activity = \App\Models\Activity::factory()->create();
        foreach (range(1, 3) as $i) {
            \App\Models\QuizAttempt::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id + $i,
                'score' => 80 + $i * 5,
                'duration_seconds' => 100 + $i * 10,
                'submitted_at' => now()->subMinutes($i)->toDateTimeString(),
            ]);
        }
        $this->actingAs($user, 'api');
        $first = $this->getJson("/api/quiz/activities/{$activity->id}/leaderboard"); // Prime cache
        $firstData = $first->json();

        \App\Models\QuizAttempt::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id + 99,
            'score' => 999,
            'duration_seconds' => 50,
            'submitted_at' => now()->toDateTimeString(),
        ]); // New high score

        $second = $this->getJson("/api/quiz/activities/{$activity->id}/leaderboard");
        $second->assertOk();
        $second->assertJsonMissing(['score' => 999]); // Cache hit, no refresh
        $this->assertEquals($firstData, $second->json()); // Cache returns same data

        cache()->forget("leaderboard_{$activity->id}"); // Invalidate cache

        $third = $this->getJson("/api/quiz/activities/{$activity->id}/leaderboard");
        $third->assertOk();
        $third->assertJsonFragment(['score' => 999]); // Cache refreshed
    }
}
