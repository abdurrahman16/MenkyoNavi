<?php

namespace App\Services\Exams;

use App\Enums\ExamScoringMode;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;

class ExamScoringService
{
    /**
     * Score a loaded attempt from its snapshots and selected answers.
     *
     * @return array{
     *     score: int,
     *     passed: bool,
     *     correct_count: int,
     *     incorrect_count: int,
     *     unanswered_count: int,
     *     questions: list<array{id: int, points_awarded: int, is_correct: bool, outcome: string}>
     * }
     */
    public function score(ExamAttempt $attempt): array
    {
        $attempt->loadMissing([
            'questions.answers',
        ]);

        $score = 0;
        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        $questions = [];

        foreach ($attempt->questions as $question) {
            $result = $this->scoreQuestion($question);
            $score += $result['points_awarded'];

            if ($result['outcome'] === 'correct') {
                $correctCount++;
            } elseif ($result['outcome'] === 'unanswered') {
                $unansweredCount++;
            } else {
                $incorrectCount++;
            }

            $questions[] = $result;
        }

        return [
            'score' => $score,
            'passed' => $score >= $attempt->passing_score,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'unanswered_count' => $unansweredCount,
            'questions' => $questions,
        ];
    }

    /**
     * @return array{correct: int, incorrect: int, unanswered: int}
     */
    public function outcomeCounts(ExamAttempt $attempt): array
    {
        $correct = 0;
        $incorrect = 0;
        $unanswered = 0;

        foreach ($attempt->questions as $question) {
            if ($question->is_correct === true) {
                $correct++;

                continue;
            }

            if ($question->answers->isEmpty()) {
                $unanswered++;

                continue;
            }

            $incorrect++;
        }

        return [
            'correct' => $correct,
            'incorrect' => $incorrect,
            'unanswered' => $unanswered,
        ];
    }

    /**
     * @return list<array{category: string, correct: int, incorrect: int, unanswered: int, points: int, max_points: int}>
     */
    public function categoryBreakdown(ExamAttempt $attempt): array
    {
        $groups = [];
        $hasCategory = false;

        foreach ($attempt->questions as $question) {
            $name = trim((string) ($question->content_snapshot['category_name'] ?? ''));

            if ($name !== '') {
                $hasCategory = true;
            } else {
                $name = 'General';
            }

            if (! isset($groups[$name])) {
                $groups[$name] = [
                    'category' => $name,
                    'correct' => 0,
                    'incorrect' => 0,
                    'unanswered' => 0,
                    'points' => 0,
                    'max_points' => 0,
                ];
            }

            $groups[$name]['max_points'] += $question->scoring_weight;
            $groups[$name]['points'] += (int) $question->points_awarded;

            if ($question->is_correct === true) {
                $groups[$name]['correct']++;
            } elseif ($question->answers->isEmpty()) {
                $groups[$name]['unanswered']++;
            } else {
                $groups[$name]['incorrect']++;
            }
        }

        if (! $hasCategory) {
            return [];
        }

        return array_values($groups);
    }

    /**
     * @return array{id: int, points_awarded: int, is_correct: bool, outcome: string}
     */
    private function scoreQuestion(ExamAttemptQuestion $question): array
    {
        if ($question->scoring_mode === ExamScoringMode::AllOrNothing) {
            return $this->scoreIllustratedGroup($question);
        }

        $answer = $question->answers->firstWhere('statement_slot', 0);

        if ($answer === null) {
            return $this->result($question, 0, false, 'unanswered');
        }

        $expected = (bool) ($question->content_snapshot['correct_answer'] ?? false);
        $isCorrect = (bool) $answer->selected_answer === $expected;

        return $this->result(
            $question,
            $isCorrect ? $question->scoring_weight : 0,
            $isCorrect,
            $isCorrect ? 'correct' : 'incorrect',
        );
    }

    /**
     * @return array{id: int, points_awarded: int, is_correct: bool, outcome: string}
     */
    private function scoreIllustratedGroup(ExamAttemptQuestion $question): array
    {
        $statements = $question->content_snapshot['statements'] ?? [];
        $answers = $question->answers->keyBy('statement_slot');
        $anyAnswered = false;
        $allCorrect = $statements !== [];

        foreach ($statements as $statement) {
            $slot = (int) ($statement['sort_order'] ?? 0);
            $answer = $answers->get($slot);

            if ($answer === null) {
                $allCorrect = false;

                continue;
            }

            $anyAnswered = true;
            $expected = (bool) ($statement['correct_answer'] ?? false);

            if ((bool) $answer->selected_answer !== $expected) {
                $allCorrect = false;
            }
        }

        if (! $anyAnswered) {
            return $this->result($question, 0, false, 'unanswered');
        }

        if ($allCorrect) {
            return $this->result($question, $question->scoring_weight, true, 'correct');
        }

        return $this->result($question, 0, false, 'incorrect');
    }

    /**
     * @return array{id: int, points_awarded: int, is_correct: bool, outcome: string}
     */
    private function result(ExamAttemptQuestion $question, int $points, bool $isCorrect, string $outcome): array
    {
        return [
            'id' => $question->id,
            'points_awarded' => $points,
            'is_correct' => $isCorrect,
            'outcome' => $outcome,
        ];
    }
}
