<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_submission_blocked(): void
    {
        $activity = Activity::factory()->create();
        $survey = Survey::create([
            'activity_id' => $activity->id,
            'title' => 'Test Survey',
            'structure_json' => [
                'questions' => [
                    ['id' => 1, 'type' => 'text', 'label' => 'Q1'],
                ],
            ],
        ]);
        $user = User::factory()->create();
        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 80,
        ]);
        $this->actingAs($user, 'api');
        $this->postJson("/api/surveys/activities/{$activity->id}/responses", [
            'response' => ['q1' => 'foo'],
        ])->assertOk();
        $response = $this->postJson("/api/surveys/activities/{$activity->id}/responses", [
            'response' => ['q1' => 'bar'],
        ]);
        $response->assertStatus(409);
        $response->assertJson([
            'reason' => 'duplicate_submission',
            'submitted' => true,
        ]);
    }
}
