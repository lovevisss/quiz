<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\QuizLotteryDraw;
use App\Models\QuizLotteryEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_user_can_view_status_and_enter_lottery(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(8),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 72,
        ]);

        $this->actingAs($authUser, 'api');

        $status = $this->getJson("/api/quiz/activities/{$activity->id}/lottery");
        $status->assertOk();
        $status->assertJson([
            'eligible' => true,
            'entered' => false,
            'entry_id' => null,
            'reason' => 'eligible',
        ]);

        $entry = $this->postJson("/api/quiz/activities/{$activity->id}/lottery");
        $entry->assertOk();
        $entry->assertJson([
            'eligible' => true,
            'entered' => true,
            'reason' => 'entered',
        ]);

        $this->assertDatabaseHas('quiz_lottery_entries', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'quiz_attempt_id' => 1,
            'score' => 72,
        ]);
    }

    public function test_lottery_entry_is_idempotent(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(8),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 80,
        ]);

        $this->actingAs($authUser, 'api');

        $this->postJson("/api/quiz/activities/{$activity->id}/lottery")->assertOk();
        $repeat = $this->postJson("/api/quiz/activities/{$activity->id}/lottery");

        $repeat->assertOk();
        $repeat->assertJson([
            'eligible' => true,
            'entered' => false,
            'reason' => 'existing_entry',
        ]);

        $this->assertSame(1, QuizLotteryEntry::count());
    }

    public function test_ineligible_user_gets_explicit_reason(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(8),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 50,
        ]);

        $this->actingAs($authUser, 'api');

        $response = $this->postJson("/api/quiz/activities/{$activity->id}/lottery");
        $response->assertOk();
        $response->assertJson([
            'eligible' => false,
            'entered' => false,
            'entry_id' => null,
            'reason' => 'score_below_threshold',
        ]);

        $this->assertDatabaseCount('quiz_lottery_entries', 0);
    }

    public function test_admin_can_draw_unique_winners_and_non_admin_is_blocked(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        /** @var User $userOne */
        $userOne = User::factory()->create();
        /** @var User $userTwo */
        $userTwo = User::factory()->create();
        /** @var Authenticatable $authAdmin */
        $authAdmin = $admin;
        /** @var Authenticatable $authUserOne */
        $authUserOne = $userOne;
        /** @var Authenticatable $authUserTwo */
        $authUserTwo = $userTwo;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $userTwo->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(8),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinutes(2),
            'score' => 88,
        ]);

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $userOne->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(9),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 91,
        ]);

        $this->actingAs($authUserOne, 'api');
        $this->postJson("/api/quiz/activities/{$activity->id}/lottery")->assertOk();
        $this->actingAs($authUserTwo, 'api');
        $this->postJson("/api/quiz/activities/{$activity->id}/lottery")->assertOk();

        $this->actingAs($authAdmin, 'api');
        $draw = $this->postJson("/api/quiz/activities/{$activity->id}/lottery/draw", ['count' => 2]);

        $draw->assertOk();
        $draw->assertJson([
            'count' => 2,
        ]);
        $draw->assertJsonStructure([
            'draw_batch',
            'count',
            'winners' => [
                ['draw_id', 'user_id', 'entry_id', 'winner_position'],
            ],
        ]);

        $winnerUserIds = collect($draw->json('winners'))->pluck('user_id')->all();
        $this->assertSame([$userOne->id, $userTwo->id], $winnerUserIds);
        $this->assertSame(2, QuizLotteryDraw::count());

        /** @var User $outsider */
        $outsider = User::factory()->create();
        /** @var Authenticatable $authOutsider */
        $authOutsider = $outsider;
        $this->actingAs($authOutsider, 'api');
        $this->postJson("/api/quiz/activities/{$activity->id}/lottery/draw")
            ->assertForbidden();
    }
}
