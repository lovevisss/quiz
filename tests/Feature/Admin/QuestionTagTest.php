<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\QuestionTag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'User', 'slug' => 'user']);
    }

    public function test_admin_can_create_question_tag(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->post(route('admin.question_tags.store'), [
            'name' => '网络安全',
        ]);

        $response->assertRedirect(route('admin.question_tags.index'));
        $this->assertDatabaseHas('question_tags', ['name' => '网络安全']);
    }

    public function test_admin_cannot_delete_question_tag_when_used_by_question(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $tag = QuestionTag::create(['name' => 'math']);

        Question::query()->create([
            'content' => 'Test Question',
            'type' => 'single',
            'options' => ['A', 'B'],
            'answer' => 'A',
            'difficulty' => 1,
            'tags' => ['math'],
            'status' => true,
        ]);

        $response = $this->delete(route('admin.question_tags.destroy', $tag));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('question_tags', ['id' => $tag->id]);
    }

    public function test_admin_can_delete_unused_question_tag(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $tag = QuestionTag::create(['name' => 'unused']);

        $response = $this->delete(route('admin.question_tags.destroy', $tag));

        $response->assertRedirect(route('admin.question_tags.index'));
        $this->assertDatabaseMissing('question_tags', ['id' => $tag->id]);
    }
}

