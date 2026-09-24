<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\PaperStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_activity()
    {
        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $response = $this->post(route('admin.activities.store'), [
            'name' => 'Test Activity',
            'description' => 'desc',
            'start_date' => '2026-04-16',
            'end_date' => '2026-04-17',
        ]);
        $response->assertRedirect(route('admin.activities.index'));
        $this->assertDatabaseHas('activities', [
            'name' => 'Test Activity',
        ]);
    }

    public function test_non_admin_cannot_access_activities()
    {
        \App\Models\Role::create(['name' => 'User', 'slug' => 'user']);
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);
        $response = $this->get(route('admin.activities.index'));
        $response->assertForbidden();
        $response = $this->post(route('admin.activities.store'), [
            'name' => 'Should Fail',
            'start_date' => '2026-04-16',
            'end_date' => '2026-04-17',
        ]);
        $response->assertForbidden();
    }

    public function test_admin_can_toggle_activity_status()
    {
        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $activity = Activity::factory()->create([
            'enabled' => true,
        ]);

        $response = $this->patch(route('admin.activities.toggle-status', $activity));

        $response->assertRedirect(route('admin.activities.index'));
        $this->assertFalse($activity->fresh()->enabled);
    }

    public function test_admin_can_bind_paper_strategy_to_activity()
    {
        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $strategy = PaperStrategy::query()->create([
            'name' => 'Random 10',
            'mode' => 'random',
            'config' => ['count' => 10],
            'status' => true,
        ]);

        $response = $this->post(route('admin.activities.store'), [
            'name' => 'Strategy Activity',
            'description' => 'desc',
            'start_date' => '2026-04-16',
            'end_date' => '2026-04-17',
            'paper_strategy_id' => $strategy->id,
        ]);

        $response->assertRedirect(route('admin.activities.index'));
        $this->assertDatabaseHas('activities', [
            'name' => 'Strategy Activity',
            'paper_strategy_id' => $strategy->id,
        ]);
    }

    public function test_admin_can_view_a_finished_activity_leaderboard_with_one_best_result_per_user(): void
    {
        $this->withoutVite();
        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $participant = User::factory()->create();
        $activity = Activity::factory()->create(['end_date' => now()->subDay()]);

        foreach ([60, 90] as $score) {
            \App\Models\QuizAttempt::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $participant->id,
                'score' => $score,
                'duration_seconds' => 120,
                'submitted_at' => now()->subDay()->addMinutes($score),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.activities.leaderboard', $activity))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ActivityLeaderboard')
                ->where('activity.id', $activity->id)
                ->has('rows', 1)
                ->where('rows.0.user_id', $participant->id)
                ->where('rows.0.score', 90)
                ->where('rows.0.rank', 1)
                ->etc());
    }

    public function test_admin_can_delete_test_activity_and_its_related_records(): void
    {
        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $activity = Activity::factory()->create();
        $otherActivity = Activity::factory()->create();
        $attempt = \App\Models\QuizAttempt::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $admin->id,
        ]);
        $otherAttempt = \App\Models\QuizAttempt::factory()->create([
            'activity_id' => $otherActivity->id,
            'user_id' => $admin->id,
        ]);
        DB::table('quiz_answers')->insert([
            'attempt_id' => $attempt->id,
            'question_id' => 1,
            'answer_payload_json' => '{}',
            'awarded_score' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $surveyId = DB::table('surveys')->insertGetId([
            'activity_id' => $activity->id,
            'title' => '测试问卷',
            'structure_json' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('survey_responses')->insert([
            'survey_id' => $surveyId,
            'activity_id' => $activity->id,
            'user_id' => $admin->id,
            'response_json' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        cache()->put("leaderboard_{$activity->id}", [['score' => 1]], 600);

        $this->actingAs($admin)
            ->delete(route('admin.activities.destroy', $activity))
            ->assertRedirect(route('admin.activities.index'));

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('quiz_answers', ['attempt_id' => $attempt->id]);
        $this->assertDatabaseMissing('surveys', ['id' => $surveyId]);
        $this->assertDatabaseMissing('survey_responses', ['activity_id' => $activity->id]);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $otherAttempt->id]);
        $this->assertFalse(cache()->has("leaderboard_{$activity->id}"));
    }

    public function test_non_admin_cannot_view_or_delete_activity_results(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.activities.leaderboard', $activity))
            ->assertForbidden();
        $this->delete(route('admin.activities.destroy', $activity))->assertForbidden();
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }
}
