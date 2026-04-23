<?php
namespace Database\Factories;

use App\Models\ExportTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExportTaskFactory extends Factory
{
    protected $model = ExportTask::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'type' => 'quiz_results',
            'params' => json_encode(['quiz_id' => 1]),
            'status' => 'completed',
            'file_path' => 'exports/test.csv',
        ];
    }
}
