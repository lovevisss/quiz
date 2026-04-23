<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\QuizCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_is_issued_from_latest_qualified_attempt(): void
    {
        $user = User::factory()->create();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinutes(10),
            'score' => 40,
        ]);

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 75,
        ]);

        $this->actingAs($authUser, 'api');

        $response = $this->getJson("/api/quiz/activities/{$activity->id}/certificate");

        $response->assertOk();
        $response->assertExactJson([
            'eligible' => true,
            'issued' => true,
            'certificate_id' => 1,
            'reason' => 'issued',
        ]);

        $this->assertDatabaseHas('quiz_certificates', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'quiz_attempt_id' => 2,
            'score' => 75,
        ]);
    }

    public function test_certificate_request_is_idempotent(): void
    {
        $user = User::factory()->create();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 92,
        ]);

        $this->actingAs($authUser, 'api');

        $first = $this->getJson("/api/quiz/activities/{$activity->id}/certificate");
        $first->assertOk();
        $first->assertJson([
            'eligible' => true,
            'issued' => true,
            'reason' => 'issued',
        ]);

        $second = $this->getJson("/api/quiz/activities/{$activity->id}/certificate");
        $second->assertOk();
        $second->assertJson([
            'eligible' => true,
            'issued' => false,
            'reason' => 'existing_certificate',
        ]);

        $this->assertSame(1, QuizCertificate::count());
    }

    public function test_certificate_is_not_issued_for_low_score_latest_attempt(): void
    {
        $user = User::factory()->create();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinutes(20),
            'score' => 88,
        ]);

        QuizAttempt::create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now()->subMinutes(2),
            'score' => 55,
        ]);

        $this->actingAs($authUser, 'api');

        $response = $this->getJson("/api/quiz/activities/{$activity->id}/certificate");

        $response->assertOk();
        $response->assertJson([
            'eligible' => false,
            'issued' => false,
            'certificate_id' => null,
            'reason' => 'score_below_threshold',
        ]);

        $this->assertDatabaseMissing('quiz_certificates', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_certificate_requires_a_submitted_attempt(): void
    {
        $user = User::factory()->create();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $authUser */
        $authUser = $user;
        $activity = Activity::factory()->create();

        $this->actingAs($authUser, 'api');

        $response = $this->getJson("/api/quiz/activities/{$activity->id}/certificate");

        $response->assertOk();
        $response->assertJson([
            'eligible' => false,
            'issued' => false,
            'certificate_id' => null,
            'reason' => 'no_submitted_attempt',
        ]);
    }
}
