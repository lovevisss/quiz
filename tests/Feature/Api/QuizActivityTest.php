<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\PaperStrategy;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_current_activity(): void
    {
        $response = $this->getJson('/api/quiz/activities/current');

        $response
            ->assertOk()
            ->assertJson([
                'data' => null,
            ]);
    }

    public function test_it_returns_questions_by_fixed_paper_strategy(): void
    {
        $question1 = Question::factory()->create();
        $question2 = Question::factory()->create();

        $strategy = PaperStrategy::query()->create([
            'name' => 'Fixed A',
            'mode' => 'fixed',
            'config' => [
                'question_ids' => [$question2->id, $question1->id],
            ],
            'status' => true,
        ]);

        $activity = Activity::factory()->create([
            'enabled' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'paper_strategy_id' => $strategy->id,
        ]);

        $response = $this->getJson('/api/quiz/activities/'.$activity->id.'/questions');

        $response->assertOk();
        $response->assertJsonPath('strategy.id', $strategy->id);
        $response->assertJsonPath('data.0.id', $question2->id);
        $response->assertJsonPath('data.1.id', $question1->id);
    }
}
