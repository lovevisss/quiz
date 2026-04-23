<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['single', 'multiple', 'text']);
        $options = $type !== 'text' ? [
            'A' => $this->faker->word,
            'B' => $this->faker->word,
            'C' => $this->faker->word,
            'D' => $this->faker->word,
        ] : null;
        $optionExplanations = $type !== 'text' ? [
            'A' => $this->faker->sentence,
            'B' => $this->faker->sentence,
            'C' => $this->faker->sentence,
            'D' => $this->faker->sentence,
        ] : null;
        return [
            'content' => $this->faker->sentence,
            'type' => $type,
            'options' => $options,
            'answer' => $type === 'multiple' ? json_encode(['A', 'C']) : ($type === 'single' ? 'A' : null),
            'explanation' => $this->faker->optional()->sentence,
            'option_explanations' => $optionExplanations,
            'difficulty' => $this->faker->numberBetween(1, 5),
            'tags' => [$this->faker->word],
            'status' => true,
        ];
    }
}
