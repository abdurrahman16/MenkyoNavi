<?php

namespace Database\Factories;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now();

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'exam_type_id' => ExamType::factory(),
            'attempt_type' => ExamAttemptType::Full,
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes(30),
            'submitted_at' => null,
            'question_count' => 1,
            'max_score' => 1,
            'score' => null,
            'passing_score' => 1,
            'passed' => null,
            'duration_seconds' => null,
            'current_position' => 1,
            'counts_toward_analytics' => true,
            'locale' => 'en',
        ];
    }
}
