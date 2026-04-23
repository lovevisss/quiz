<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_survey_structure(): void
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
        $this->actingAs($user, 'api');
        $response = $this->getJson("/api/surveys/activities/{$activity->id}/current");
        $response->assertOk();
        $response->assertJson([
            'survey_id' => $survey->id,
            'title' => 'Test Survey',
        ]);
    }

    public function test_submit_survey_success_and_lottery_eligible(): void
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
        $response = $this->postJson("/api/surveys/activities/{$activity->id}/responses", [
            'response' => ['q1' => 'foo'],
        ]);
        $response->assertOk();
        $response->assertJson([
            'submitted' => true,
            'lottery_eligible' => true,
        ]);
        $this->assertDatabaseHas('survey_responses', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_submit_survey_forbidden_if_no_quiz(): void
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
        $this->actingAs($user, 'api');
        $response = $this->postJson("/api/surveys/activities/{$activity->id}/responses", [
            'response' => ['q1' => 'foo'],
        ]);
        $response->assertStatus(403);
        $response->assertJson([
            'reason' => 'quiz_not_completed',
        ]);
    }
}
