<?php

namespace Tests\Feature\Api;

use App\Models\User;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAntiCheatTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_submit(): void
    {
        $user = User::factory()->withoutTwoFactor()->createOne();
        $this->actingAs($user, 'api'); // Ensure $user is treated as Authenticatable
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'submitted_at' => now()]);

        $response = $this->actingAs($user)->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Attempt already submitted', 'anti_cheat_flags' => ['duplicate_submit' => true]]);
    }

    public function test_expired_attempt(): void
    {
        $user = User::factory()->withoutTwoFactor()->createOne();
        $this->actingAs($user, 'api'); // Ensure $user is treated as Authenticatable
        $attempt = QuizAttempt::factory()->create(['user_id' => $user->id, 'expires_at' => now()->subMinute()]);

        $response = $this->actingAs($user)->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Attempt expired', 'anti_cheat_flags' => ['expired_attempt' => true]]);
    }
}