<?php

namespace Database\Factories;

use App\Models\QuizAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAnswerFactory extends Factory
{
    protected $model = QuizAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => $this->faker->randomNumber(),
            'question_id' => $this->faker->randomNumber(),
            'answer_payload_json' => json_encode(['option' => 'A']),
            'is_correct' => $this->faker->boolean(),
            'awarded_score' => $this->faker->numberBetween(0, 10),
            'answered_at' => now(),
        ];
    }
}