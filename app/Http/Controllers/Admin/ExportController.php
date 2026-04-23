<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportTask;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();
        $task = ExportTask::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'type' => $request->input('type', 'quiz'),
            'params' => $request->input('params', []),
        ]);
        // 审计日志
        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'export',
            'action' => 'create',
            'target' => $task->id,
            'payload' => ['type' => $task->type, 'params' => $task->params],
        ]);
        // 这里模拟异步，直接生成文件
        $file = 'exports/export_'.$task->id.'.csv';
        Storage::disk('local')->put($file, "id,name\n1,Test\n");
        $task->update(['status' => 'completed', 'file_path' => $file]);
        return response()->json(['id' => $task->id, 'status' => $task->status, 'file_path' => $file], 201);
    }

    public function status($id)
    {
        $task = ExportTask::findOrFail($id);
        return response()->json([
            'id' => $task->id,
            'status' => $task->status,
            'file_path' => $task->file_path,
        ]);
    }

    public function download($id)
    {
        $task = ExportTask::findOrFail($id);
        if ($task->status !== 'completed' || !$task->file_path) {
            abort(404);
        }
        return Storage::disk('local')->download($task->file_path);
    }
}
