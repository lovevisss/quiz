<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_cas_before_answering(): void
    {
        $response = $this->get('/quiz/question?activity=123');

        $response->assertRedirect(route('cas.redirect', [
            'return' => '/quiz/question?activity=123',
        ]));
    }

    public function test_authenticated_user_can_open_question_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/quiz/question?activity=123');

        $response->assertOk();
    }

    public function test_guest_cannot_fetch_activity_questions(): void
    {
        $activity = Activity::factory()->create([
            'enabled' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->getJson('/api/quiz/activities/'.$activity->id.'/questions')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_activity_questions(): void
    {
        $user = User::factory()->create();
        Question::factory()->create(['type' => 'single']);
        $activity = Activity::factory()->create([
            'enabled' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/quiz/activities/'.$activity->id.'/questions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
