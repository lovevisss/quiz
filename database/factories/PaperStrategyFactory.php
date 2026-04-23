<?php

namespace Database\Factories;

use App\Models\PaperStrategy;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaperStrategyFactory extends Factory
{
    protected $model = PaperStrategy::class;

    public function definition(): array
    {
        $mode = $this->faker->randomElement(['fixed', 'random']);
        $config = $mode === 'fixed' ? ['question_ids' => [1,2,3]] : ['count' => 10, 'tags' => ['math']];
        return [
            'name' => $this->faker->unique()->words(2, true),
            'mode' => $mode,
            'config' => $config,
            'status' => true,
        ];
    }
}
