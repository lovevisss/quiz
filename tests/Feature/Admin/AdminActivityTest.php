<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\PaperStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
