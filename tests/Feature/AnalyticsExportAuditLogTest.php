<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ExportTask;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyticsExportAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    // --- Analytics API ---
    public function test_admin_can_access_analytics_overview()
    {
        $this->withSession(['_token' => 'test']);
        $this->actingAs($this->admin);
        $this->assertNotNull(\Illuminate\Support\Facades\Auth::user(), 'No user authenticated before request');
        $this->assertTrue(\Illuminate\Support\Facades\Auth::user()->is_admin, 'Authenticated user is not admin');
        $response = $this->get('/admin/analytics/overview');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'participants', 'completion_rate', 'average_score'
        ]);
    }

    public function test_non_admin_cannot_access_analytics_overview()
    {
        $response = $this->actingAs($this->user)->get('/admin/analytics/overview');
        $response->assertStatus(403);
    }

    // --- Export Task API ---
    public function test_admin_can_create_and_download_export_task()
    {
        Storage::fake('local');
        $this->actingAs($this->admin, 'web');
        $response = $this->post('/admin/export/tasks', [
            'type' => 'quiz_results',
            'params' => ['quiz_id' => 1],
        ]);
        $response->assertStatus(201);
        $taskId = $response->json('id');
        $this->assertNotNull($taskId);

        // Check status
        $this->actingAs($this->admin, 'web');
        $statusResp = $this->get("/admin/export/tasks/{$taskId}");
        $statusResp->assertStatus(200);
        $statusResp->assertJsonStructure(['id', 'status', 'file_path']);

        // Simulate file exists for download
        $task = ExportTask::find($taskId);
        $task->file_path = 'exports/test.csv';
        $task->status = 'completed';
        $task->save();
        Storage::disk('local')->put('exports/test.csv', 'id,name,score\n1,Alice,90');

        $this->actingAs($this->admin, 'web');
        $downloadResp = $this->get("/admin/export/tasks/{$taskId}/download");
        $downloadResp->assertStatus(200);
        $downloadResp->assertHeader('content-disposition');
    }

    public function test_non_admin_cannot_create_export_task()
    {
        $response = $this->actingAs($this->user)->post('/admin/export/tasks', [
            'type' => 'quiz_results',
            'params' => ['quiz_id' => 1],
        ]);
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_download_export_task()
    {
        $task = ExportTask::factory()->create(['user_id' => $this->admin->id, 'status' => 'completed', 'file_path' => 'exports/test.csv']);
        $response = $this->actingAs($this->user)->get("/admin/export/tasks/{$task->id}/download");
        $response->assertStatus(403);
    }

    // --- Audit Log API ---
    public function test_admin_can_view_audit_logs()
    {
        AuditLog::factory()->create(['user_id' => $this->admin->id, 'module' => 'export', 'action' => 'create', 'target' => 'quiz_results']);
        $this->actingAs($this->admin, 'web');
        $response = $this->get('/admin/audit/logs');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [['id', 'user_id', 'module', 'action', 'target', 'payload']]]);
    }

    public function test_non_admin_cannot_view_audit_logs()
    {
        $response = $this->actingAs($this->user)->get('/admin/audit/logs');
        $response->assertStatus(403);
    }
}
