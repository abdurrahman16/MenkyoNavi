<?php

namespace Tests\Feature\Exams;

use App\Enums\ContentReviewStatus;
use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionStatement;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartExamAttemptTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_authenticated_user_starts_a_full_attempt_from_the_configured_pool(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 1, 4, 30);
        $first = $this->publishedTrueFalse($examType, true);
        $second = $this->publishedTrueFalse($examType, false);
        $illustrated = $this->publishedIllustrated($examType);
        $demo = Question::factory()->demo()->create(['correct_answer' => true]);
        $demo->examTypes()->attach($examType->id);
        $unreviewed = Question::factory()->create([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Pending,
            'is_demo' => false,
        ]);
        $unreviewed->examTypes()->attach($examType->id);
        $shortGroup = Question::factory()->illustrated()->create([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Reviewed,
            'is_demo' => false,
            'correct_answer' => null,
        ]);
        QuestionStatement::factory()->create(['question_id' => $shortGroup->id, 'sort_order' => 1]);
        QuestionStatement::factory()->create(['question_id' => $shortGroup->id, 'sort_order' => 2]);
        $shortGroup->examTypes()->attach($examType->id);
        $otherType = ExamType::factory()->create();
        $foreign = $this->publishedTrueFalse($otherType, true);

        $this->actingAs($user)
            ->post(route('exams.attempts.store', $examType), ['attempt_type' => 'full'])
            ->assertRedirect();

        $attempt = ExamAttempt::query()->with('questions')->first();
        $selectedIds = $attempt->questions->pluck('question_id')->sort()->values()->all();
        $expectedIds = collect([$first->id, $second->id, $illustrated->id])->sort()->values()->all();

        $this->assertSame($expectedIds, $selectedIds);
        $this->assertNotContains($demo->id, $selectedIds);
        $this->assertNotContains($unreviewed->id, $selectedIds);
        $this->assertNotContains($shortGroup->id, $selectedIds);
        $this->assertNotContains($foreign->id, $selectedIds);
        $this->assertSame(ExamAttemptType::Full, $attempt->attempt_type);
        $this->assertSame(3, $attempt->question_count);
        $this->assertSame(4, $attempt->max_score);
        $this->assertSame(4, $attempt->passing_score);
        $this->assertTrue($attempt->counts_toward_analytics);
        $this->assertSame(now()->addMinutes(30)->getTimestamp(), $attempt->expires_at->getTimestamp());
        $this->assertSame(2, $attempt->questions->firstWhere('question_id', $illustrated->id)->scoring_weight);
        $this->assertSame(QuestionType::IllustratedGroup, $attempt->questions->firstWhere('question_id', $illustrated->id)->question_type);
    }

    public function test_full_exam_is_refused_when_reviewed_questions_are_insufficient(): void
    {
        $this->seed(ExamTypeSeeder::class);
        $user = $this->learner();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->firstOrFail();
        $onlyOne = $this->publishedTrueFalse($gaimen, true);

        $this->actingAs($user)
            ->from(route('exams.gaimen-kirikae'))
            ->post(route('exams.attempts.store', $gaimen), ['attempt_type' => 'full'])
            ->assertRedirect(route('exams.gaimen-kirikae'))
            ->assertSessionHas('exam_error');

        $this->assertSame(0, ExamAttempt::query()->count());
        $this->assertNotNull($onlyOne->id);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.gaimen-kirikae'))
            ->assertSuccessful()
            ->assertSee(__('exams.full_practice_unavailable', [], 'en'), false)
            ->assertSee('45 / 50', false);
    }

    public function test_demo_attempt_uses_the_sample_pool_and_labels_the_shortfall(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);
        $user = $this->learner();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->firstOrFail();
        $published = $this->publishedTrueFalse($gaimen, true, [
            'japanese_text' => '公開問題はデモに混ぜない。',
        ]);

        $this->actingAs($user)
            ->post(route('exams.attempts.store', $gaimen), ['attempt_type' => 'demo'])
            ->assertRedirect();

        $attempt = ExamAttempt::query()->with('questions.question')->firstOrFail();

        $this->assertSame(ExamAttemptType::Demo, $attempt->attempt_type);
        $this->assertSame(9, $attempt->question_count);
        $this->assertSame(9, $attempt->max_score);
        $this->assertSame(9, $attempt->passing_score);
        $this->assertFalse($attempt->counts_toward_analytics);
        $this->assertTrue($attempt->questions->every(fn ($row): bool => $row->question->is_demo && $row->question->status === QuestionStatus::Demo));
        $this->assertFalse($attempt->questions->contains('question_id', $published->id));
        $this->assertFalse($attempt->questions->contains('question_uuid', '22222222-2222-4222-8222-222222222201'));

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee(__('exams.demo_banner_title', [], 'en'), false)
            ->assertSee('9', false)
            ->assertDontSee('official examination readiness', false);
    }

    public function test_demo_start_is_refused_when_demo_mode_is_disabled(): void
    {
        config(['exams.demo_enabled' => false]);
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);
        $user = $this->learner();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->firstOrFail();

        $this->actingAs($user)
            ->from(route('exams.gaimen-kirikae'))
            ->post(route('exams.attempts.store', $gaimen), ['attempt_type' => 'demo'])
            ->assertRedirect(route('exams.gaimen-kirikae'))
            ->assertSessionHas('exam_error');

        $this->assertSame(0, ExamAttempt::query()->count());
        $this->actingAs($user)
            ->get(route('exams.gaimen-kirikae'))
            ->assertDontSee('Start demo practice', false);
    }

    public function test_starting_again_resumes_the_open_attempt(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);

        $this->actingAs($user)->post(route('exams.attempts.store', $examType), ['attempt_type' => 'full']);
        $this->actingAs($user)->post(route('exams.attempts.store', $examType), ['attempt_type' => 'full']);

        $this->assertSame(1, ExamAttempt::query()->count());
        $this->assertSame(ExamAttemptStatus::InProgress, ExamAttempt::query()->first()->status);
    }

    public function test_guests_are_redirected_to_login_when_starting_an_exam(): void
    {
        $examType = $this->examTypeWithRules(1, 0, 1);

        $this->post(route('exams.attempts.store', $examType), ['attempt_type' => 'full'])
            ->assertRedirect(route('login'));
    }

    public function test_public_exam_routes_remain_available(): void
    {
        $this->seed(ExamTypeSeeder::class);

        $this->withSession(['locale' => 'en'])->get('/exams')->assertSuccessful();
        $this->withSession(['locale' => 'en'])->get('/exams/gaimen-kirikae')->assertSuccessful()->assertSee('Practice exam', false);
        $this->get('/exams/karimen')->assertSuccessful();
        $this->get('/exams/honmen')->assertSuccessful();
        $this->get('/exams/attempts')->assertRedirect(route('login'));
    }
}
