<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\QuestionFeedback;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'User', 'slug' => 'user']);
    }

    public function test_admin_can_crud_questions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $payload = [
            'content' => 'Test Question',
            'type' => 'single',
            'options' => ['A', 'B', 'C', 'D'],
            'answer' => 'A',
            'difficulty' => 2,
            'tags' => ['math'],
            'status' => true,
        ];

        $this->post(route('admin.questions.store'), $payload)
            ->assertRedirect(route('admin.questions.create'));
        $this->assertDatabaseHas('questions', ['content' => 'Test Question']);

        $question = Question::firstOrFail();
        $update = [
            'content' => 'Updated Question',
            'type' => 'single',
            'options' => ['A', 'B'],
            'answer' => 'B',
            'difficulty' => 3,
            'tags' => ['science'],
            'status' => false,
        ];

        $this->put(route('admin.questions.update', $question), $update)
            ->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('questions', [
            'content' => 'Updated Question',
            'answer' => 'B',
            'status' => false,
        ]);

        $this->delete(route('admin.questions.destroy', $question))
            ->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseMissing('questions', ['content' => 'Updated Question']);
    }

    public function test_admin_can_view_separate_question_create_and_manage_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->get(route('admin.questions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/QuestionCreate'));

        $this->get(route('admin.questions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Questions'));
    }

    public function test_admin_can_filter_questions_by_tag_and_sort_by_feedback_or_likes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $questionOne = Question::factory()->create([
            'content' => 'Security Basics',
            'tags' => ['安全'],
        ]);
        $questionTwo = Question::factory()->create([
            'content' => 'Law Basics',
            'tags' => ['法规'],
        ]);
        $questionThree = Question::factory()->create([
            'content' => 'Security Advanced',
            'tags' => ['安全'],
        ]);

        foreach (range(1, 3) as $index) {
            QuestionFeedback::query()->create([
                'question_id' => $questionTwo->id,
                'user_id' => User::factory()->create()->id,
                'liked' => true,
                'correction_text' => $index === 1 ? '法规题需要补充说明' : null,
                'correction_status' => 'pending',
            ]);
        }

        foreach (range(1, 2) as $index) {
            QuestionFeedback::query()->create([
                'question_id' => $questionThree->id,
                'user_id' => User::factory()->create()->id,
                'liked' => true,
                'correction_text' => "安全题反馈 {$index}",
                'correction_status' => 'pending',
            ]);
        }

        QuestionFeedback::query()->create([
            'question_id' => $questionOne->id,
            'user_id' => User::factory()->create()->id,
            'liked' => true,
            'correction_text' => null,
            'correction_status' => 'pending',
        ]);

        $this->get(route('admin.questions.index', [
            'tag' => '安全',
            'sort' => 'likes_desc',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions')
                ->where('filters.tag', '安全')
                ->where('filters.sort', 'likes_desc')
                ->has('questions.data', 2)
                ->where('questions.data.0.id', $questionThree->id)
                ->where('questions.data.1.id', $questionOne->id));

        $this->get(route('admin.questions.index', [
            'sort' => 'feedback_desc',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions')
                ->where('filters.sort', 'feedback_desc')
                ->where('questions.data.0.id', $questionThree->id)
                ->where('questions.data.1.id', $questionTwo->id));
    }

    public function test_admin_can_search_filter_type_status_and_paginate_questions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Question::factory()->count(25)->create([
            'type' => 'single',
            'status' => true,
        ]);

        $target = Question::factory()->create([
            'content' => 'Mobile security special question',
            'type' => 'multiple',
            'status' => false,
        ]);

        $this->get(route('admin.questions.index', [
            'q' => 'special',
            'type' => 'multiple',
            'status' => 'inactive',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions')
                ->where('filters.q', 'special')
                ->where('filters.type', 'multiple')
                ->where('filters.status', 'inactive')
                ->has('questions.data', 1)
                ->where('questions.data.0.id', $target->id));

        $this->get(route('admin.questions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions')
                ->has('questions.data', 20)
                ->where('questions.total', 26));
    }

    public function test_non_admin_cannot_access_questions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        $this->get(route('admin.questions.index'))->assertForbidden();
        $this->get(route('admin.questions.create'))->assertForbidden();
        $this->post(route('admin.questions.store'), [
            'content' => 'Should Fail',
            'type' => 'single',
            'options' => ['A'],
            'answer' => 'A',
            'difficulty' => 1,
            'tags' => [],
            'status' => true,
        ])->assertForbidden();
    }
}
