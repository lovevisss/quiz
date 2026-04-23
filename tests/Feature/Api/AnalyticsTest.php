<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_activity_analytics(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->roles()->attach($adminRole->id);
        $activity = Activity::factory()->create();

        // 5 participants, 3 submitted, scores: 80, 90, 100
        foreach (range(1, 5) as $i) {
            $user = User::factory()->create();
            QuizAttempt::create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'status' => $i <= 3 ? 'submitted' : 'in_progress',
                'started_at' => now()->subMinutes(10 + $i),
                'expires_at' => now()->addMinutes(10),
                'submitted_at' => $i <= 3 ? now()->subMinutes($i) : null,
                'score' => $i <= 3 ? 70 + $i * 10 : 0,
            ]);
        }

        $this->actingAs($admin, 'api');
        $response = $this->getJson("/api/quiz/activities/{$activity->id}/analytics");
        $response->assertOk();
        $response->assertJsonStructure([
            'participants',
            'completion_rate',
            'average_score',
        ]);
        $data = $response->json();
        $this->assertSame(5, $data['participants']);
        $this->assertEquals(0.6, $data['completion_rate']); // 3/5
        $this->assertEquals(90.0, $data['average_score']); // (80+90+100)/3
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $activity = Activity::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->getJson("/api/quiz/activities/{$activity->id}/analytics");
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Forbidden']);
    }
}
