<?php

namespace Tests\Feature;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\DuplicatePairDecision;
use App\Enums\EditorialExamCategory;
use App\Enums\EditorialIssueType;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\DuplicatePairReview;
use App\Livewire\Admin\QuestionBankDashboard;
use App\Livewire\Admin\QuestionBankIndex;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\Question;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Translations\QuestionBankReadinessReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_non_administrators_cannot_access_editorial_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $entry = QuestionImportEntry::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.questions.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.questions.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.questions.show', $entry))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.questions.bulk-classification'))
            ->assertForbidden();
    }

    #[Test]
    public function test_administrators_can_access_dashboard_and_paginated_index(): void
    {
        $admin = User::factory()->administrator()->create();
        QuestionImportEntry::factory()->count(30)->create();

        $this->actingAs($admin)
            ->get(route('admin.questions.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.dashboard_title', [], 'ja'));

        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class)
            ->assertOk()
            ->assertSee(__('admin.staged_questions', [], 'ja'))
            ->assertViewHas('entries', function ($entries): bool {
                return $entries->count() === 25 && $entries->total() === 30;
            });
    }

    #[Test]
    public function test_classification_updates_create_audit_history_without_changing_original_english(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Immutable question text.',
            'english_explanation' => 'Immutable explanation text.',
            'correct_answer' => true,
            'exam_family' => 'karimen',
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('selectedCategories', [
                EditorialExamCategory::Karimen->value,
                EditorialExamCategory::GeneralTrafficRules->value,
            ])
            ->set('approveClassification', true)
            ->call('saveClassification')
            ->assertHasNoErrors();

        $entry->refresh();

        $this->assertSame('Immutable question text.', $entry->english_question);
        $this->assertSame('Immutable explanation text.', $entry->english_explanation);
        $this->assertTrue($entry->correct_answer);
        $this->assertSame('karimen', $entry->exam_family);
        $this->assertTrue($entry->classification_approved);
        $this->assertEqualsCanonicalizing(
            [EditorialExamCategory::Karimen->value, EditorialExamCategory::GeneralTrafficRules->value],
            $entry->editorial_categories,
        );
        $this->assertDatabaseHas('question_import_review_audits', [
            'question_import_entry_id' => $entry->id,
            'user_id' => $admin->id,
            'action' => 'classification_updated',
        ]);
    }

    #[Test]
    public function test_answer_key_and_content_rights_reviews_do_not_overwrite_original_answer(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create([
            'correct_answer' => false,
            'english_question' => 'Keep me.',
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('answerKeyStatus', AnswerKeyReviewStatus::Disputed->value)
            ->set('answerKeyNotes', 'Needs research against official handbook.')
            ->set('proposedCorrectAnswer', '1')
            ->set('proposedAnswerNotes', 'Suspect true based on chapter 3.')
            ->call('saveAnswerKeyReview')
            ->assertHasNoErrors()
            ->set('contentRightsStatus', ContentRightsReviewStatus::PermissionRequired->value)
            ->set('contentRightsNotes', 'No license on file.')
            ->set('contentRightsEvidence', '')
            ->call('saveContentRights')
            ->assertHasNoErrors();

        $entry->refresh();

        $this->assertFalse($entry->correct_answer);
        $this->assertSame('Keep me.', $entry->english_question);
        $this->assertTrue($entry->proposed_correct_answer);
        $this->assertSame(AnswerKeyReviewStatus::Disputed, $entry->answer_key_status);
        $this->assertSame(ContentRightsReviewStatus::PermissionRequired, $entry->content_rights_status);
        $this->assertFalse($entry->is_published);
    }

    #[Test]
    public function test_content_rights_approval_requires_evidence(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create();

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('contentRightsStatus', ContentRightsReviewStatus::ApprovedForPublicReuse->value)
            ->set('contentRightsNotes', '')
            ->set('contentRightsEvidence', '')
            ->call('saveContentRights')
            ->assertHasErrors('content_rights_evidence');

        $this->assertSame(ContentRightsReviewStatus::Unclear, $entry->fresh()->content_rights_status);
    }

    #[Test]
    public function test_duplicate_pair_review_records_decision_and_preserves_both_entries(): void
    {
        $admin = User::factory()->administrator()->create();
        $left = QuestionImportEntry::factory()->create([
            'english_question' => 'Left wording about stopping.',
            'duplicate_kind' => QuestionImportDuplicateKind::SimilarWordingNeedsReview,
        ]);
        $right = QuestionImportEntry::factory()->create([
            'english_question' => 'Right wording about stopping.',
        ]);
        $left->forceFill(['duplicate_of_entry_id' => $right->id])->save();

        $review = QuestionImportDuplicateReview::factory()->create([
            'entry_id' => min($left->id, $right->id),
            'related_entry_id' => max($left->id, $right->id),
            'similarity_reason' => 'similar_wording_needs_review',
        ]);

        Livewire::actingAs($admin)
            ->test(DuplicatePairReview::class, ['review' => $review->id])
            ->set('decision', DuplicatePairDecision::DifferentMeaning->value)
            ->set('notes', 'Different scenarios despite similar verbs.')
            ->call('saveDecision')
            ->assertHasNoErrors();

        $review->refresh();
        $this->assertSame(DuplicatePairDecision::DifferentMeaning, $review->decision);
        $this->assertSame($admin->id, $review->reviewed_by);
        $this->assertNotNull($review->reviewed_at);
        $this->assertDatabaseCount('question_import_entries', 2);
        $this->assertDatabaseHas('question_import_review_audits', [
            'action' => 'duplicate_pair_reviewed',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function test_editorial_issue_updates_and_existing_translations_are_preserved(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create();
        $translation = QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => 'パイロット日本語',
            'explanation' => 'パイロット解説',
            'status' => StagedTranslationStatus::Reviewed,
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('selectedIssues', [EditorialIssueType::AmbiguousEnglish->value])
            ->set('editorialNotes', 'Clarify absolute wording.')
            ->set('workflowStatus', EditorialWorkflowStatus::InReview->value)
            ->call('saveEditorialIssues')
            ->assertHasNoErrors();

        $translation->refresh();
        $this->assertSame('パイロット日本語', $translation->question_text);
        $this->assertSame(StagedTranslationStatus::Reviewed, $translation->status);
        $this->assertContains(EditorialIssueType::AmbiguousEnglish->value, $entry->fresh()->editorial_issues);
        $this->assertFalse($entry->fresh()->is_published);
    }

    #[Test]
    public function test_index_filters_by_source_and_missing_translation(): void
    {
        $admin = User::factory()->administrator()->create();
        $withJa = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'english_question' => 'Unique karimen filter question AAA.',
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $withJa->id,
            'locale' => 'ja',
            'question_text' => 'あり',
            'explanation' => 'あり',
        ]);
        $honmen = QuestionImportEntry::factory()->create([
            'source_group' => 'honmen_dl_1',
            'english_question' => 'Unique honmen filter question BBB.',
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class)
            ->set('sourceGroup', 'karimen_test_1')
            ->assertSee('Unique karimen filter question AAA.')
            ->assertDontSee('Unique honmen filter question BBB.')
            ->set('sourceGroup', '')
            ->set('missingTranslation', 'ja')
            ->assertSee('Unique honmen filter question BBB.')
            ->assertDontSee('Unique karimen filter question AAA.');

        $this->assertNotSame($withJa->id, $honmen->id);
    }

    #[Test]
    public function test_readiness_statistics_remain_available_and_grant_admin_command_works(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'email' => 'editor@example.test']);
        QuestionImportEntry::factory()->count(2)->create();

        $this->assertSame(0, Artisan::call('menkyo:grant-admin', ['email' => 'editor@example.test']));
        $this->assertTrue($user->fresh()->isAdministrator());

        $report = app(QuestionBankReadinessReporter::class)->report();
        $this->assertSame(2, $report['staged_questions_total']);
        $this->assertArrayHasKey('ja', $report['translations_with_text_by_locale']);

        Livewire::actingAs($user->fresh())
            ->test(QuestionBankDashboard::class)
            ->assertOk()
            ->assertSee(__('admin.staged_entries', [], 'ja'));
    }

    #[Test]
    public function test_exam_runner_questions_and_no_automatic_publication(): void
    {
        $admin = User::factory()->administrator()->create();
        $question = Question::factory()->create([
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
            'japanese_text' => '既存の問題文',
        ]);
        $entry = QuestionImportEntry::factory()->create(['is_published' => false]);

        app(EditorialReviewService::class)->updateClassification(
            $entry,
            $admin,
            [EditorialExamCategory::Honmen->value],
            true,
        );

        $question->refresh();
        $entry->refresh();

        $this->assertSame('既存の問題文', $question->japanese_text);
        $this->assertSame(QuestionStatus::Demo, $question->status);
        $this->assertFalse($entry->is_published);
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertGreaterThan(0, QuestionImportReviewAudit::query()->count());
    }
}
