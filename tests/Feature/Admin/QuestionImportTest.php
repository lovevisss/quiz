<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'slug' => 'admin']);
        Role::create(['name' => 'User', 'slug' => 'user']);
    }

    public function test_admin_can_import_questions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $payload = [
            'questions' => [
                [
                    'content' => 'Bulk Q1',
                    'type' => 'single',
                    'options' => ['A', 'B', 'C'],
                    'answer' => 'A',
                    'difficulty' => 2,
                    'tags' => ['tag1'],
                    'status' => true,
                ],
                [
                    'content' => 'Bulk Q2',
                    'type' => 'multiple',
                    'options' => ['A', 'B', 'C', 'D'],
                    'answer' => 'A,B',
                    'difficulty' => 3,
                    'tags' => ['tag2'],
                    'status' => true,
                ],
            ]
        ];
        $response = $this->postJson(route('admin.questions.import'), $payload);
        $response->assertCreated();
        $this->assertDatabaseHas('questions', ['content' => 'Bulk Q1']);
        $this->assertDatabaseHas('questions', ['content' => 'Bulk Q2']);
    }

    public function test_import_requires_validation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $payload = [
            'questions' => [
                [
                    'type' => 'single', // missing content
                    'options' => ['A'],
                    'answer' => 'A',
                    'difficulty' => 1,
                    'tags' => [],
                    'status' => true,
                ]
            ]
        ];
        $response = $this->postJson(route('admin.questions.import'), $payload);
        $response->assertStatus(422);
    }

    public function test_non_admin_cannot_import()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);
        $payload = [
            'questions' => [
                [
                    'content' => 'Should Fail',
                    'type' => 'single',
                    'options' => ['A'],
                    'answer' => 'A',
                    'difficulty' => 1,
                    'tags' => [],
                    'status' => true,
                ]
            ]
        ];
        $response = $this->postJson(route('admin.questions.import'), $payload);
        $response->assertForbidden();
    }

    public function test_admin_can_import_questions_from_csv_file()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $csv = implode("\n", [
            'content,type,options,answer,difficulty,tags,status',
            'CSV Q1,single,"A,B,C",A,2,"math,grade1",true',
            'CSV Q2,multiple,"A,B,C,D","A,B",3,"science",true',
        ]);

        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $response = $this->post(route('admin.questions.import'), [
            'file' => $file,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('questions', ['content' => 'CSV Q1']);
        $this->assertDatabaseHas('questions', ['content' => 'CSV Q2']);
    }

    public function test_import_skips_duplicate_questions_by_content_and_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Question::query()->create([
            'content' => 'Duplicate Q',
            'type' => 'single',
            'options' => ['A', 'B'],
            'answer' => 'A',
            'difficulty' => 1,
            'tags' => ['math'],
            'status' => true,
        ]);

        $payload = [
            'questions' => [
                [
                    'content' => 'Duplicate Q',
                    'type' => 'single',
                    'options' => ['A', 'B'],
                    'answer' => 'A',
                    'difficulty' => 1,
                    'tags' => ['math'],
                    'status' => true,
                ],
                [
                    'content' => 'Unique Q',
                    'type' => 'single',
                    'options' => ['A', 'B'],
                    'answer' => 'B',
                    'difficulty' => 2,
                    'tags' => ['science'],
                    'status' => true,
                ],
            ],
        ];

        $response = $this->postJson(route('admin.questions.import'), $payload);
        $response->assertCreated();

        $this->assertEquals(1, Question::query()->where('content', 'Duplicate Q')->count());
        $this->assertDatabaseHas('questions', ['content' => 'Unique Q']);
    }

    public function test_admin_can_download_import_template(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $response = $this->get(route('admin.questions.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSee('序号,题目,选项 A,选项 B,选项 C,选项 D,正确项,解析,标签,类型,难度,启用', false);
    }
}
