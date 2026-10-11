<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptType;
use App\Livewire\ExamRunner;
use App\Models\QuestionTranslation;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamQuestionPresenter;
use App\Support\LocalePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamSingleLanguagePresentationTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_japanese_mode_shows_only_japanese_stem_without_side_by_side_english(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $question = $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '一時停止の標識では必ず停止する。',
        ]);
        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'en',
            'question_text' => 'You must always stop at a stop sign.',
        ]);

        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);

        $this->actingAs($user)
            ->withSession(['locale' => 'ja'])
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('一時停止の標識では必ず停止する。', false)
            ->assertDontSee('You must always stop at a stop sign.', false)
            ->assertDontSee('Hide translation', false)
            ->assertDontSee('Show translation', false)
            ->assertDontSee('English fallback', false);
    }

    public function test_english_mode_shows_english_translation_without_japanese_stem(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $question = $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '徐行しなければならない。',
        ]);
        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'en',
            'question_text' => 'You must proceed slowly.',
            'explanation' => 'Slow down near hazards.',
        ]);

        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('You must proceed slowly.', false)
            ->assertDontSee('徐行しなければならない。', false)
            ->assertDontSee('English fallback', false);
    }

    public function test_missing_translation_does_not_silently_use_english_as_selected_locale(): void
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
        ]);

        // Start in Japanese so the full pool is available, then switch UI language.
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
            ->assertSee(__('exams.translation_unavailable', [], 'vi'), false)
            ->assertDontSee('English fallback sentence about the priority road.', false)
            ->assertSee('優先道路を走る。', false);
    }

    public function test_answer_correctness_is_independent_of_display_language(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $question = $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '信号が赤なら停止する。',
        ]);
        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'en',
            'question_text' => 'Stop when the light is red.',
        ]);

        $service = app(ExamAttemptService::class);
        $this->withSession(['locale' => 'en']);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->assertTrue($final->passed);
        $this->assertSame(1, $final->score);
    }

    public function test_language_switch_does_not_reset_timer_or_lose_answers(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => '問題一。']);
        $this->publishedTrueFalse($examType, false, ['japanese_text' => '問題二。']);

        $service = app(ExamAttemptService::class);
        $this->withSession(['locale' => 'ja']);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $first = $attempt->questions->first();
        $service->recordAnswer($attempt, $first->id, 0, true);
        $expiresAt = $attempt->fresh()->expires_at->copy();

        $this->actingAs($user)
            ->from(route('exams.attempts.show', $attempt))
            ->post(route('locale.switch', 'en'))
            ->assertRedirect(route('exams.attempts.show', $attempt))
            ->assertCookie(LocalePreference::COOKIE_NAME, 'en');

        $fresh = $attempt->fresh()->loadForDisplay();
        $this->assertTrue($fresh->expires_at->equalTo($expiresAt));
        $this->assertNotNull($fresh->questions->first()->answers->first());
        $this->assertSame(ExamAttemptType::Full, $fresh->attempt_type);
        $this->assertSame(1, $fresh->questions->filter(fn ($row) => $row->answers->isNotEmpty())->count());

        session(['locale' => 'en']);

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $fresh])
            ->assertSet('attemptId', $fresh->id)
            ->assertSee(__('exams.answered', [], 'en'), false);
    }

    public function test_full_exam_cannot_start_when_selected_locale_pool_lacks_translations(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '日本語のみの問題。',
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'ko'])
            ->post(route('exams.attempts.store', $examType), [
                'attempt_type' => 'full',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('exam_attempts', 0);
    }
}
