<?php

namespace Tests\Feature\Admin;

use App\Models\PaperStrategy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaperStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'User', 'slug' => 'user']);
    }

    public function test_admin_can_crud_paper_strategies()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Create
        $payload = [
            'name' => 'Test Strategy',
            'mode' => 'fixed',
            'config' => ['question_ids' => [1,2,3]],
            'status' => true,
        ];
        $response = $this->post(route('admin.paper_strategies.store'), $payload);
        $response->assertRedirect(route('admin.paper_strategies.index'));
        $this->assertDatabaseHas('paper_strategies', ['name' => 'Test Strategy']);

        $strategy = PaperStrategy::first();

        // Update
        $update = [
            'name' => 'Updated Strategy',
            'mode' => 'random',
            'config' => ['count' => 10, 'tags' => ['math']],
            'status' => false,
        ];
        $response = $this->put(route('admin.paper_strategies.update', $strategy), $update);
        $response->assertRedirect(route('admin.paper_strategies.index'));
        $this->assertDatabaseHas('paper_strategies', ['name' => 'Updated Strategy', 'mode' => 'random']);

        // Delete
        $response = $this->delete(route('admin.paper_strategies.destroy', $strategy));
        $response->assertRedirect(route('admin.paper_strategies.index'));
        $this->assertDatabaseMissing('paper_strategies', ['name' => 'Updated Strategy']);
    }

    public function test_non_admin_cannot_access_paper_strategies()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);
        $response = $this->get(route('admin.paper_strategies.index'));
        $response->assertForbidden();
        $response = $this->post(route('admin.paper_strategies.store'), [
            'name' => 'Should Fail',
            'mode' => 'fixed',
            'config' => ['question_ids' => [1]],
            'status' => true,
        ]);
        $response->assertForbidden();
    }
}
