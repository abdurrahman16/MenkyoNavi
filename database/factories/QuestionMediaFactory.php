<?php

namespace Database\Factories;

use App\Enums\QuestionMediaType;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionMedia>
 */
class QuestionMediaFactory extends Factory
{
    protected $model = QuestionMedia::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->illustrated(),
            'disk' => 'web',
            'path' => 'images/demo/intersection-preview.svg',
            'media_type' => QuestionMediaType::Illustration,
            'alt_text' => 'Demonstration intersection illustration',
            'mime_type' => 'image/svg+xml',
            'width' => 480,
            'height' => 320,
            'sort_order' => 0,
        ];
    }
}
