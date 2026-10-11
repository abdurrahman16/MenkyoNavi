<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptType;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionTranslation;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamQuestionPresenter;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamResultsTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_results_page_matches_the_stored_score(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $category = QuestionCategory::factory()->create([
            'name' => 'Signals',
            'slug' => 'signals-results',
        ]);
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true, [
            'question_category_id' => $category->id,
            'japanese_text' => '信号で停止する。',
        ]);
        $this->publishedTrueFalse($examType, false, [
            'question_category_id' => $category->id,
            'japanese_text' => '徐行しなくてよい。',
        ]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $first = $attempt->questions->firstWhere('position', 1);
        $service->recordAnswer(
            $attempt,
            $first->id,
            0,
            (bool) $first->content_snapshot['correct_answer'],
        );
        $this->travel(2)->minutes();
        $final = $service->submit($attempt->fresh());

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.results', $final))
            ->assertSuccessful()
            ->assertSee('1 / 2', false)
            ->assertSee('50%', false)
            ->assertSee('Practice benchmark not reached', false)
            ->assertSee('Correct', false)
            ->assertSee('Incorrect', false)
            ->assertSee('Unanswered', false)
            ->assertSee('No incorrect answers in this session.', false)
            ->assertSee('Unanswered questions', false)
            ->assertSee('2:00', false)
            ->assertSee('Signals', false)
            ->assertSee('Practice simulation', false);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.index'))
            ->assertSuccessful()
            ->assertSee($examType->name, false)
            ->assertSee('1 / 2', false);
    }

    public function test_review_stays_hidden_until_the_attempt_is_finalized(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true, [
            'explanation_ja' => 'REVIEW_EXPLANATION_MARKER',
            'japanese_text' => '横断歩道で停止する。',
        ]);
        QuestionTranslation::factory()->create([
            'question_id' => Question::query()->first()->id,
            'locale' => 'en',
            'question_text' => 'Stop at the crosswalk.',
            'explanation' => 'REVIEW_EXPLANATION_MARKER',
            'nuance_note' => 'Yield to people on foot.',
        ]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $this->actingAs($user)
            ->get(route('exams.attempts.review', $attempt))
            ->assertRedirect(route('exams.attempts.show', $attempt));

        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, false);
        $final = $service->submit($attempt->fresh());

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.review', $final))
            ->assertSuccessful()
            ->assertSee('Stop at the crosswalk.', false)
            ->assertDontSee('横断歩道で停止する。', false)
            ->assertSee('Your answer', false)
            ->assertSee('False (×)', false)
            ->assertSee('Correct answer', false)
            ->assertSee('True (○)', false)
            ->assertSee('REVIEW_EXPLANATION_MARKER', false)
            ->assertSee('Yield to people on foot.', false);
    }

    public function test_missing_translation_shows_unavailable_notice_without_english_disguise(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $question = $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '優先道路を走る。',
        ]);
        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'en',
            'question_text' => 'English fallback sentence about the priority road.',
            'explanation' => 'Priority roads change who goes first.',
        ]);
        $this->withSession(['locale' => 'ja']);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);

        $resolved = app(ExamQuestionPresenter::class)->resolveTranslation(
            $attempt->questions->first()->content_snapshot['translations'],
            'vi',
        );
        $this->assertFalse($resolved['available']);
        $this->assertNull($resolved['text']);

        $this->actingAs($user)
            ->withSession(['locale' => 'vi'])
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('優先道路を走る。', false)
            ->assertSee(__('exams.translation_unavailable', [], 'vi'), false)
            ->assertDontSee('English fallback sentence about the priority road.', false)
            ->assertDontSee('Vietnamese invented sentence', false);
    }

    public function test_demo_results_do_not_claim_official_readiness(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);
        $user = $this->learner();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->firstOrFail();
        $attempt = app(ExamAttemptService::class)->start($user, $gaimen, ExamAttemptType::Demo);
        $final = app(ExamAttemptService::class)->submit($attempt);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.results', $final))
            ->assertSuccessful()
            ->assertSee('Demo mode', false)
            ->assertDontSee('official examination readiness', false);
    }
}
