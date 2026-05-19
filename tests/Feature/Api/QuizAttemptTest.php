<?php

namespace Tests\Feature\Api;

use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_attempt(): void
    {
        $user = User::factory()->create()->fresh();
        $this->actingAs($user->fresh(), 'api');
        $response = $this->postJson('/api/quiz/activities/1/attempts');

        $response->assertStatus(201);
        $this->assertDatabaseHas('quiz_attempts', [
            'activity_id' => 1,
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_save_answer(): void
{
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user, 'api');
    $attempt = QuizAttempt::factory()->create(['user_id' => $user->id]);

    $response = $this->putJson("/api/quiz/attempts/{$attempt->id}/answers/1", [
        'answer' => ['option' => 'A']
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('quiz_answers', [
        'attempt_id' => $attempt->id,
        'question_id' => 1,
    ]);

    $nonOwner = User::factory()->create();
    $this->actingAs($nonOwner, 'api');
    $response = $this->putJson("/api/quiz/attempts/{$attempt->id}/answers/1", [
        'answer' => ['option' => 'B']
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Forbidden']);

    $response = $this->putJson("/api/quiz/attempts/999/answers/1", [
        'answer' => ['option' => 'C']
    ]);

    $response->assertStatus(404);
    $response->assertJson(['error' => 'Attempt not found']);
}

    public function test_submit_attempt(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'expires_at' => now()->addMinutes(10)]);
        $this->actingAs($user, 'api');

        $response = $this->postJson("/api/quiz/attempts/{$attempt->id}/submit");
        $response->assertStatus(200);
        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'submitted',
        ]);

        $nonOwner = User::factory()->create();
        $this->actingAs($nonOwner, 'api');
        $response = $this->postJson("/api/quiz/attempts/{$attempt->id}/submit");
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Forbidden']);
    }

    public function test_get_result_owner(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'score' => 80]);
        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/quiz/attempts/{$attempt->id}/result");
        $response->assertStatus(200);
        $response->assertJson(['score' => 80]);

        $nonOwner = User::factory()->create();
        $this->actingAs($nonOwner, 'api');
        $response = $this->getJson("/api/quiz/attempts/{$attempt->id}/result");
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Forbidden']);

        $response = $this->getJson("/api/quiz/attempts/999999/result");
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Attempt not found']);
    }

    public function test_result_contains_selected_and_correct_choice_display_for_wrong_answers(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $question = Question::factory()->create([
            'type' => 'single',
            'content' => '网络安全中哪项更安全？',
            'options' => ['密码过短', '启用双重验证', '所有网站同一密码', '关闭安全提醒'],
            'answer' => 'B',
            'explanation' => '启用双重验证可以显著提高账户安全性。',
        ]);
        $attempt = QuizAttempt::factory()->create([
            'activity_id' => 9,
            'user_id' => $user->id,
            'score' => 0,
            'submitted_at' => now(),
        ]);

        QuizAnswer::query()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_payload_json' => ['selected_option' => 'A'],
            'is_correct' => false,
            'awarded_score' => 0,
            'answered_at' => now(),
        ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/quiz/attempts/{$attempt->id}/result");

        $response->assertOk();
        $response->assertJson([
            'activity_id' => 9,
            'total_questions' => 1,
            'correct_count' => 0,
            'wrong_count' => 1,
            'wrong_questions' => [
                [
                    'question_id' => $question->id,
                    'selected_answer_label' => 'A',
                    'selected_answer_text' => '密码过短',
                    'selected_answer_display' => 'A. 密码过短',
                    'correct_answer_label' => 'B',
                    'correct_answer_text' => '启用双重验证',
                    'correct_answer_display' => 'B. 启用双重验证',
                    'explanation' => '启用双重验证可以显著提高账户安全性。',
                ],
            ],
        ]);
    }

    public function test_result_returns_newly_unlocked_achievements_for_the_attempt(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $startedAt = now()->subSeconds(20);
        $attempt = QuizAttempt::factory()->create([
            'activity_id' => 8,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => $startedAt,
            'submitted_at' => $startedAt->copy()->addSeconds(20),
            'duration_seconds' => 20,
            'score' => 3,
        ]);

        foreach (range(1, 3) as $index) {
            $question = Question::factory()->create();

            QuizAnswer::query()->create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_payload_json' => ['selected_option' => 'A'],
                'is_correct' => true,
                'awarded_score' => 1,
                'answered_at' => $startedAt->copy()->addSeconds($index * 3),
            ]);
        }

        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/quiz/attempts/{$attempt->id}/result");

        $response->assertOk();
        $response->assertJsonFragment(['key' => 'join_first']);
        $response->assertJsonFragment(['key' => 'zero_breakthrough']);
        $response->assertJsonFragment(['key' => 'lightning_hand']);
        $response->assertJsonFragment(['key' => 'speed_run']);
        $response->assertJsonFragment(['key' => 'triple_star']);
    }
}
