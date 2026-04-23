<?php

namespace Tests\Feature\Admin;

use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyTemplateTest extends TestCase
{
    protected function extractInertia($html)
    {
        if (preg_match('/<div id="app" data-page="([^"]+)"/', $html, $matches)) {
            return json_decode(html_entity_decode($matches[1]), true);
        }
        $this->fail('Could not extract Inertia payload from response.');
    }

    protected $admin;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_list_survey_templates()
    {
        SurveyTemplate::factory()->count(2)->create();
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.survey_templates.index'));
        $response->assertStatus(200);
        $inertia = $this->extractInertia($response->getContent());
$this->assertSame('Admin/SurveyTemplates', $inertia['component']);
    }

    public function test_admin_can_create_survey_template()
    {
        $data = [
            'name' => '满意度调查',
            'description' => '年度满意度问卷',
            'status' => true,
        ];
        $response = $this->actingAs($this->admin, 'web')->post(route('admin.survey_templates.store'), $data);
        $response->assertRedirect(route('admin.survey_templates.index'));
        $this->assertDatabaseHas('survey_templates', ['name' => '满意度调查']);
    }

    public function test_admin_can_edit_survey_template()
    {
        $template = SurveyTemplate::factory()->create();
        $data = [
            'name' => '新名称',
            'description' => '新描述',
            'status' => false,
        ];
        $response = $this->actingAs($this->admin, 'web')->put(route('admin.survey_templates.update', $template), $data);
        $response->assertRedirect(route('admin.survey_templates.index'));
        $this->assertDatabaseHas('survey_templates', ['id' => $template->id, 'name' => '新名称', 'status' => false]);
    }

    public function test_admin_can_delete_survey_template()
    {
        $template = SurveyTemplate::factory()->create();
        $response = $this->actingAs($this->admin, 'web')->delete(route('admin.survey_templates.destroy', $template));
        $response->assertRedirect();
        $this->assertDatabaseMissing('survey_templates', ['id' => $template->id]);
    }

    public function test_guest_cannot_access_survey_templates()
    {
        $response = $this->get(route('admin.survey_templates.index'));
        $response->assertRedirect('/login');
    }
}
