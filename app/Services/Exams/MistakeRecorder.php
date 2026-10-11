<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptType;
use App\Models\ExamAttempt;
use App\Models\UserQuestionMistake;

class MistakeRecorder
{
    public function recordFromAttempt(ExamAttempt $attempt): void
    {
        if ($attempt->user_id === null || ! $attempt->counts_toward_analytics) {
            return;
        }

        if ($attempt->attempt_type === ExamAttemptType::Demo || $attempt->attempt_type === ExamAttemptType::AdminPreview) {
            return;
        }

        $attempt->loadMissing(['questions.question', 'questions.answers']);

        foreach ($attempt->questions as $attemptQuestion) {
            $question = $attemptQuestion->question;

            if ($question === null || $question->is_demo || $attemptQuestion->question_id === null) {
                continue;
            }

            if ($attemptQuestion->is_correct === true || $attemptQuestion->answers->isEmpty()) {
                continue;
            }

            $mistake = UserQuestionMistake::query()->firstOrNew([
                'user_id' => $attempt->user_id,
                'question_id' => $attemptQuestion->question_id,
            ]);

            $mistake->exam_type_id = $attempt->exam_type_id;
            $mistake->last_exam_attempt_id = $attempt->id;
            $mistake->incorrect_count = $mistake->exists ? $mistake->incorrect_count + 1 : 1;
            $mistake->last_incorrect_at = now();
            $mistake->save();
        }
    }
}
