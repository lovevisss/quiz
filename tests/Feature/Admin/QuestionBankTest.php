<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'User', 'slug' => 'user']);
    }

    public function test_admin_can_crud_questions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        // Create
        $payload = [
            'content' => 'Test Question',
            'type' => 'single',
            'options' => ['A', 'B', 'C', 'D'],
            'answer' => 'A',
            'difficulty' => 2,
            'tags' => ['math'],
            'status' => true,
        ];
        $response = $this->post(route('admin.questions.store'), $payload);
        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('questions', ['content' => 'Test Question']);

        $question = Question::first();

        // Update
        $update = ['content' => 'Updated Question', 'type' => 'single', 'options' => ['A', 'B'], 'answer' => 'B', 'difficulty' => 3, 'tags' => ['science'], 'status' => false];
        $response = $this->put(route('admin.questions.update', $question), $update);
        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('questions', ['content' => 'Updated Question', 'answer' => 'B']);

        // Delete
        $response = $this->delete(route('admin.questions.destroy', $question));
        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseMissing('questions', ['content' => 'Updated Question']);
    }

    public function test_non_admin_cannot_access_questions()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);
        $response = $this->get(route('admin.questions.index'));
        $response->assertForbidden();
        $response = $this->post(route('admin.questions.store'), [
            'content' => 'Should Fail',
            'type' => 'single',
            'options' => ['A'],
            'answer' => 'A',
            'difficulty' => 1,
            'tags' => [],
            'status' => true,
        ]);
        $response->assertForbidden();
    }
}
