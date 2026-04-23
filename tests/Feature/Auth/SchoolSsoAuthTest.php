<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\MockSchoolSsoAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSsoAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_sso_callback_logs_in_user(): void
    {
        $response = $this->postJson('/auth/sso/mock/callback', [
            'token' => 'mock-token',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['email' => 'mockuser@example.com']);
        $this->assertAuthenticated();
    }

    public function test_unauthenticated_user_redirected_from_admin_activities(): void
    {
        $response = $this->get('/admin/activities');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_non_admin_user_forbidden_from_admin_activities(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $response = $this->get('/admin/activities');
        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_activities(): void
    {
        $this->actingAsAdmin();
        $response = $this->get('/admin/activities');
        $response->assertOk();
    }

    private function actingAsAdmin(): void
    {
        $user = \App\Models\User::factory()->create();
        \App\Models\Role::factory()->create(['name' => 'admin']);
        $user->assignRole('admin');
        $this->actingAs($user->fresh());
    }
}
