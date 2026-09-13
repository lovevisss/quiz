<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_redirected_to_quiz()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('quiz.index'));
    }

    public function test_admin_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_users_with_admin_role_can_visit_the_dashboard()
    {
        $role = Role::query()->create(['name' => 'admin', 'slug' => 'admin']);
        $user = User::factory()->create(['is_admin' => false]);
        $user->roles()->attach($role->id);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }
}
