<?php

namespace Tests\Feature\Admin;

use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyQuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected SurveyTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->template = SurveyTemplate::factory()->create();
    }

    public function test_admin_can_list_survey_questions(): void
    {
        SurveyQuestion::factory()->count(2)->create(['survey_template_id' => $this->template->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.survey_questions.index', $this->template));

        $response->assertOk();
        $this->assertSame('Admin/SurveyQuestions', $this->extractInertia($response->getContent())['component']);
    }

    public function test_admin_can_create_survey_question(): void
    {
        $data = [
            'content' => '您的年龄？',
            'type' => 'single',
            'options' => ['18-25', '26-35', '36+'],
            'required' => true,
            'order' => 1,
        ];

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.survey_questions.store', $this->template), $data)
            ->assertRedirect(route('admin.survey_questions.index', $this->template));

        $this->assertDatabaseHas('survey_questions', [
            'content' => '您的年龄？',
            'survey_template_id' => $this->template->id,
        ]);
    }

    public function test_admin_can_edit_survey_question(): void
    {
        $question = SurveyQuestion::factory()->create(['survey_template_id' => $this->template->id]);
        $data = [
            'content' => '新问题',
            'type' => 'multi',
            'options' => ['A', 'B'],
            'required' => false,
            'order' => 2,
        ];

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.survey_questions.update', [$this->template, $question]), $data)
            ->assertRedirect(route('admin.survey_questions.index', $this->template));

        $this->assertDatabaseHas('survey_questions', [
            'id' => $question->id,
            'content' => '新问题',
            'required' => false,
        ]);
    }

    public function test_admin_can_delete_survey_question(): void
    {
        $question = SurveyQuestion::factory()->create(['survey_template_id' => $this->template->id]);

        $this->actingAs($this->admin, 'web')
            ->delete(route('admin.survey_questions.destroy', [$this->template, $question]))
            ->assertRedirect();

        $this->assertDatabaseMissing('survey_questions', ['id' => $question->id]);
    }

    public function test_guest_cannot_access_survey_questions(): void
    {
        $this->get(route('admin.survey_questions.index', $this->template))
            ->assertRedirect('/login');
    }

    protected function extractInertia(string $html): array
    {
        if (preg_match('/<div id="app" data-page="([^"]+)"/', $html, $matches)) {
            return json_decode(html_entity_decode($matches[1]), true);
        }

        $this->fail('Could not extract Inertia payload from response.');
    }
}
