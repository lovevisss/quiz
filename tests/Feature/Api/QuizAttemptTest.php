<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
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
}