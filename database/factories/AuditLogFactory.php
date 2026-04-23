<?php
namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'module' => 'export',
            'action' => 'create',
            'target' => 'quiz_results',
            'payload' => json_encode(['quiz_id' => 1]),
        ];
    }
}
