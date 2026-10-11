<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptType;
use App\Models\ExamType;
use App\Models\QuestionCategory;
use App\Models\UserQuestionMistake;
use App\Services\Exams\ExamAttemptService;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakeVaultTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    private function answerAttemptByQuestionOutcome(ExamAttemptService $service, $attempt, int $missedQuestionId, int $correctQuestionId): void
    {
        foreach ([1, 2] as $expectedPosition) {
            $attempt = $attempt->fresh()->loadForDisplay();
            $current = $attempt->questions->firstWhere('position', $expectedPosition);
            $correctAnswer = (bool) $current->content_snapshot['correct_answer'];
            $selected = $current->question_id === $missedQuestionId
                ? ! $correctAnswer
                : $correctAnswer;
            $service->recordAnswer($attempt, $current->id, 0, $selected);
            $this->assertContains($current->question_id, [$missedQuestionId, $correctQuestionId]);
        }
    }

    public function test_incorrect_full_exam_answers_are_saved_once_per_question(): void
    {
        $user = $this->learner();
        $category = QuestionCategory::factory()->create([
            'name' => 'Road Signs',
            'slug' => 'road-signs-vault',
        ]);
        $examType = $this->examTypeWithRules(2, 0, 2);
        $missed = $this->publishedTrueFalse($examType, true, [
            'question_category_id' => $category->id,
            'japanese_text' => '一時停止しなければならない。',
            'explanation_ja' => '完全に停止する。',
        ]);
        $correct = $this->publishedTrueFalse($examType, false, [
            'question_category_id' => $category->id,
            'japanese_text' => '赤信号で進んでよい。',
        ]);
        $service = app(ExamAttemptService::class);

        $first = $service->start($user, $examType, ExamAttemptType::Full);
        $this->answerAttemptByQuestionOutcome($service, $first, $missed->id, $correct->id);
        $service->submit($first);
        $service->submit($first->fresh());

        $this->travel(1)->minute();
        $second = $service->start($user, $examType, ExamAttemptType::Full);
        $this->answerAttemptByQuestionOutcome($service, $second, $missed->id, $correct->id);
        $service->submit($second);

        $this->assertSame(1, UserQuestionMistake::query()->count());
        $mistake = UserQuestionMistake::query()->first();
        $this->assertTrue($mistake->question->is($missed));
        $this->assertSame(2, $mistake->incorrect_count);

        $this->actingAs($user)
            ->get(route('mistakes.index'))
            ->assertSuccessful()
            ->assertSee('Road Signs', false)
            ->assertSee('一時停止しなければならない。', false)
            ->assertSee('完全に停止する。', false)
            ->assertDontSee('赤信号で進んでよい。', false);
    }

    public function test_unanswered_and_demo_sessions_do_not_fill_the_vault(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);
        $user = $this->learner();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->firstOrFail();
        $service = app(ExamAttemptService::class);
        $demo = $service->start($user, $gaimen, ExamAttemptType::Demo);
        $demoQuestion = $demo->questions->firstWhere('position', 1);
        $correct = (bool) $demoQuestion->content_snapshot['correct_answer'];
        $service->recordAnswer($demo, $demoQuestion->id, 0, ! $correct);
        $service->submit($demo);

        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service->submit($service->start($user, $examType, ExamAttemptType::Full));

        $this->assertSame(0, UserQuestionMistake::query()->count());
        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('mistakes.index'))
            ->assertSuccessful()
            ->assertSee(__('mistakes.empty', [], 'en'), false);
    }
}
