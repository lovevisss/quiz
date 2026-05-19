<?php

namespace Tests\Feature\Api;

use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_achievements_endpoint_returns_submitted_and_perfect_counts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $perfectAttempt = QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'activity_id' => 1,
            'score' => 2,
            'submitted_at' => now()->subMinute(),
        ]);

        $regularAttempt = QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'activity_id' => 2,
            'score' => 0,
            'submitted_at' => now(),
        ]);

        $questionA = Question::factory()->create();
        $questionB = Question::factory()->create();
        $questionC = Question::factory()->create();

        QuizAnswer::query()->create([
            'attempt_id' => $perfectAttempt->id,
            'question_id' => $questionA->id,
            'answer_payload_json' => ['selected_option' => 'A'],
            'is_correct' => true,
            'awarded_score' => 1,
            'answered_at' => now(),
        ]);

        QuizAnswer::query()->create([
            'attempt_id' => $perfectAttempt->id,
            'question_id' => $questionB->id,
            'answer_payload_json' => ['selected_option' => 'B'],
            'is_correct' => true,
            'awarded_score' => 1,
            'answered_at' => now(),
        ]);

        QuizAnswer::query()->create([
            'attempt_id' => $regularAttempt->id,
            'question_id' => $questionC->id,
            'answer_payload_json' => ['selected_option' => 'A'],
            'is_correct' => false,
            'awarded_score' => 0,
            'answered_at' => now(),
        ]);

        QuizAttempt::factory()->create([
            'user_id' => $otherUser->id,
            'activity_id' => 3,
            'score' => 5,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/quiz/achievements');

        $response->assertOk();
        $response->assertJson([
            'summary' => [
                'submitted_attempts' => 2,
                'perfect_attempts' => 1,
            ],
            'data' => [
                [
                    'key' => 'join_first',
                    'unlocked' => true,
                    'progress' => '1/1',
                ],
                [
                    'key' => 'streak_3',
                    'title' => '累计参与者',
                    'unlocked' => false,
                    'progress' => '2/3',
                ],
                [
                    'key' => 'perfect_score',
                    'unlocked' => true,
                    'progress' => '1/1',
                ],
            ],
        ]);

        $response->assertJsonPath('data.0.progress_meta.percent', 100);
        $response->assertJsonPath('data.1.progress_meta.current', 2);
        $response->assertJsonPath('data.1.progress_meta.target', 3);
        $response->assertJsonPath('data.1.progress_meta.percent', 67);
    }

    public function test_achievements_endpoint_unlocks_speed_and_streak_badges(): void
    {
        $user = User::factory()->create();
        $startedAt = now()->subSeconds(25);
        $attempt = QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'activity_id' => 11,
            'started_at' => $startedAt,
            'submitted_at' => $startedAt->copy()->addSeconds(25),
            'duration_seconds' => 25,
            'score' => 10,
            'status' => 'submitted',
        ]);

        foreach (range(1, 10) as $index) {
            $question = Question::factory()->create();

            QuizAnswer::query()->create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_payload_json' => ['selected_option' => 'A'],
                'is_correct' => true,
                'awarded_score' => 1,
                'answered_at' => $startedAt->copy()->addSeconds($index * 2),
            ]);
        }

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/quiz/achievements');

        $response->assertOk();
        $response->assertJson([
            'summary' => [
                'submitted_attempts' => 1,
                'perfect_attempts' => 1,
                'max_correct_streak' => 10,
                'max_fast_streak' => 10,
                'fastest_correct_seconds' => 2,
                'fastest_perfect_run_seconds' => 25,
            ],
        ]);

        $response->assertJsonFragment(['key' => 'zero_breakthrough', 'unlocked' => true]);
        $response->assertJsonFragment(['key' => 'lightning_hand', 'unlocked' => true, 'progress' => '最快 2 秒']);
        $response->assertJsonFragment(['key' => 'rapid_master', 'unlocked' => true, 'progress' => '10/10']);
        $response->assertJsonFragment(['key' => 'speed_run', 'unlocked' => true, 'progress' => '最快 25 秒']);
        $response->assertJsonFragment(['key' => 'triple_star', 'unlocked' => true, 'progress' => '3/3']);
        $response->assertJsonFragment(['key' => 'perfect_ten', 'unlocked' => true, 'progress' => '10/10']);
        $response->assertJsonPath('data.4.progress_meta.percent', 100);
        $response->assertJsonPath('data.6.progress_meta.current', 25);
        $response->assertJsonPath('data.6.progress_meta.target', 30);
        $response->assertJsonPath('data.6.progress_meta.percent', 100);
    }
}

