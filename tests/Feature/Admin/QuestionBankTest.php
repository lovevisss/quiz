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
        $response->assertRedirect(route('admin.questions.create'));
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
                ->has('questions', 2)
                ->where('questions.0.id', $questionThree->id)
                ->where('questions.1.id', $questionOne->id)
            );

        $this->get(route('admin.questions.index', [
            'sort' => 'feedback_desc',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Questions')
                ->where('filters.sort', 'feedback_desc')
                ->where('questions.0.id', $questionThree->id)
                ->where('questions.1.id', $questionTwo->id)
            );
    }

    public function test_non_admin_cannot_access_questions()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);
        $response = $this->get(route('admin.questions.index'));
        $response->assertForbidden();
        $response = $this->get(route('admin.questions.create'));
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
