<?php

namespace Database\Factories;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamType>
 */
class ExamTypeFactory extends Factory
{
    protected $model = ExamType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'name_ja' => '試験',
            'description' => fake()->sentence(),
            'selection_rules' => [
                'standard_true_false_count' => 50,
                'illustrated_group_count' => 0,
            ],
            'duration_minutes' => 30,
            'max_score' => 50,
            'passing_score' => 45,
            'is_active' => true,
        ];
    }
}
