<?php

namespace Tests\Feature\Admin;

use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyQuestionTest extends TestCase
{
    protected function extractInertia($html)
    {
        if (preg_match('/<div id="app" data-page="([^"]+)"/', $html, $matches)) {
            return json_decode(html_entity_decode($matches[1]), true);
        }
        $this->fail('Could not extract Inertia payload from response.');
    }

    protected $admin;
    protected $template;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->template = SurveyTemplate::factory()->create();
    }

    public function test_admin_can_list_survey_questions()
    {
        SurveyQuestion::factory()->count(2)->create(['survey_template_id' => $this->template->id]);
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.survey_templates.questions.index', $this->template));
        $response->assertStatus(200);
        $inertia = $this->extractInertia($response->getContent());
$this->assertSame('Admin/SurveyQuestions', $inertia['component']);
    }

    public function test_admin_can_create_survey_question()
    {
        $data = [
            'content' => '您的年龄？',
            'type' => 'single',
            'options' => ['18-25', '26-35', '36+'],
            'required' => true,
            'order' => 1,
        ];
        $response = $this->actingAs($this->admin, 'web')->post(route('admin.survey_templates.questions.store', $this->template), $data);
        $response->assertRedirect(route('admin.survey_templates.questions.index', $this->template));
        $this->assertDatabaseHas('survey_questions', ['content' => '您的年龄？', 'survey_template_id' => $this->template->id]);
    }

    public function test_admin_can_edit_survey_question()
    {
        $question = SurveyQuestion::factory()->create(['survey_template_id' => $this->template->id]);
        $data = [
            'content' => '新问题',
            'type' => 'multi',
            'options' => ['A', 'B'],
            'required' => false,
            'order' => 2,
        ];
        $response = $this->actingAs($this->admin, 'web')->put(route('admin.survey_templates.questions.update', [$this->template, $question]), $data);
        $response->assertRedirect(route('admin.survey_templates.questions.index', $this->template));
        $this->assertDatabaseHas('survey_questions', ['id' => $question->id, 'content' => '新问题', 'required' => false]);
    }

    public function test_admin_can_delete_survey_question()
    {
        $question = SurveyQuestion::factory()->create(['survey_template_id' => $this->template->id]);
        $response = $this->actingAs($this->admin, 'web')->delete(route('admin.survey_templates.questions.destroy', [$this->template, $question]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('survey_questions', ['id' => $question->id]);
    }

    public function test_guest_cannot_access_survey_questions()
    {
        $response = $this->get(route('admin.survey_templates.questions.index', $this->template));
        $response->assertRedirect('/login');
    }
}
