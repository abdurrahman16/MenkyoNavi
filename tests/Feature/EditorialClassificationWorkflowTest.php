<?php

namespace Tests\Feature;

use App\Enums\ContentRightsReviewStatus;
use App\Enums\DuplicatePairDecision;
use App\Enums\EditorialExamCategory;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\DuplicatePairReview;
use App\Livewire\Admin\QuestionBankBulkClassification;
use App\Livewire\Admin\QuestionBankDashboard;
use App\Livewire\Admin\QuestionBankIndex;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\Question;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\ClassificationProposalService;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Translations\StagedTranslationExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditorialClassificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_karimen_and_honmen_proposals_are_generated_from_source_groups(): void
    {
        $admin = User::factory()->administrator()->create();
        $karimen = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_3',
            'exam_family' => 'karimen',
            'english_question' => 'Karimen original question text.',
            'english_explanation' => 'Karimen original explanation.',
            'correct_answer' => true,
        ]);
        $honmen = QuestionImportEntry::factory()->create([
            'source_group' => 'honmen_dl_2',
            'exam_family' => 'honmen',
            'english_question' => 'Honmen original question text.',
            'english_explanation' => 'Honmen original explanation.',
            'correct_answer' => false,
        ]);

        $stats = app(ClassificationProposalService::class)->generateExamClassificationProposals($admin);

        $this->assertSame(1, $stats['karimen']);
        $this->assertSame(1, $stats['honmen']);
        $this->assertSame(2, $stats['updated']);

        $karimen->refresh();
        $honmen->refresh();

        $this->assertSame(EditorialExamCategory::Karimen->value, $karimen->proposed_exam_category);
        $this->assertSame(ClassificationProposalService::SOURCE_GROUP_KARIMEN, $karimen->classification_proposal_source);
        $this->assertFalse($karimen->classification_approved);
        $this->assertSame(EditorialExamCategory::Honmen->value, $honmen->proposed_exam_category);
        $this->assertSame('Karimen original question text.', $karimen->english_question);
        $this->assertTrue($karimen->correct_answer);
        $this->assertSame('Honmen original explanation.', $honmen->english_explanation);
        $this->assertFalse($honmen->correct_answer);
        $this->assertFalse($karimen->is_published);
        $this->assertFalse($honmen->is_published);
    }

    #[Test]
    public function test_second_source_entries_remain_unclassified_for_exam_eligibility(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'jaf_traffic_signals_set',
            'exam_family' => 'unspecified',
            'english_question' => 'Second-source signal question.',
            'correct_answer' => true,
        ]);

        app(ClassificationProposalService::class)->generateExamClassificationProposals($admin);
        app(ClassificationProposalService::class)->generateStudyTopicProposals($admin);

        $entry->refresh();

        $this->assertSame(EditorialExamCategory::Unclassified->value, $entry->proposed_exam_category);
        $this->assertFalse($entry->classification_approved);
        $this->assertContains(EditorialExamCategory::Unclassified->value, $entry->editorial_categories ?? []);
        $this->assertContains(EditorialStudyTopic::TrafficSignals->value, $entry->study_topics ?? []);
        $this->assertFalse($entry->study_topics_approved);
        $this->assertFalse($entry->is_published);
        $this->assertSame(ContentRightsReviewStatus::Unclear, $entry->content_rights_status);
    }

    #[Test]
    public function test_safe_bulk_approval_preserves_approved_decisions_and_writes_audit(): void
    {
        $admin = User::factory()->administrator()->create();
        $proposals = app(ClassificationProposalService::class);
        $editorial = app(EditorialReviewService::class);

        $pending = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'exam_family' => 'karimen',
            'english_question' => 'Pending bulk question.',
            'correct_answer' => false,
        ]);
        $approved = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_2',
            'exam_family' => 'karimen',
            'english_question' => 'Already approved question.',
            'correct_answer' => true,
            'editorial_categories' => [EditorialExamCategory::Karimen->value],
            'classification_approved' => true,
            'proposed_exam_category' => EditorialExamCategory::Karimen->value,
            'classification_proposal_source' => ClassificationProposalService::SOURCE_GROUP_KARIMEN,
        ]);

        $proposals->generateExamClassificationProposals($admin);

        $stats = $editorial->bulkApproveExamClassifications(
            $admin,
            [$pending->id, $approved->id],
            EditorialExamCategory::Karimen,
            overrideApproved: false,
        );

        $this->assertSame(1, $stats['updated']);
        $this->assertSame(1, $stats['skipped_approved']);

        $pending->refresh();
        $approved->refresh();

        $this->assertTrue($pending->classification_approved);
        $this->assertContains(EditorialExamCategory::Karimen->value, $pending->editorial_categories ?? []);
        $this->assertTrue($approved->classification_approved);
        $this->assertSame('Pending bulk question.', $pending->english_question);
        $this->assertFalse($pending->correct_answer);
        $this->assertFalse($pending->is_published);
        $this->assertDatabaseHas('question_import_review_audits', [
            'question_import_entry_id' => $pending->id,
            'user_id' => $admin->id,
            'action' => 'classification_updated',
        ]);
    }

    #[Test]
    public function test_bulk_classification_ui_requires_confirmation_and_is_admin_only(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'honmen_dl_1',
            'exam_family' => 'honmen',
            'proposed_exam_category' => EditorialExamCategory::Honmen->value,
            'classification_proposal_source' => ClassificationProposalService::SOURCE_GROUP_HONMEN,
            'editorial_categories' => [EditorialExamCategory::Honmen->value],
        ]);

        $this->actingAs($user)
            ->get(route('admin.questions.bulk-classification'))
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(QuestionBankBulkClassification::class)
            ->set('focusCategory', EditorialExamCategory::Honmen->value)
            ->call('toggleSelect', $entry->id)
            ->call('approveSelected')
            ->assertHasErrors(['confirmBulk']);

        $this->assertFalse($entry->fresh()->classification_approved);

        Livewire::actingAs($admin)
            ->test(QuestionBankBulkClassification::class)
            ->set('focusCategory', EditorialExamCategory::Honmen->value)
            ->call('toggleSelect', $entry->id)
            ->set('confirmBulk', true)
            ->call('approveSelected')
            ->assertHasNoErrors();

        $this->assertTrue($entry->fresh()->classification_approved);
        $this->assertFalse($entry->fresh()->is_published);
    }

    #[Test]
    public function test_topic_classification_and_review_queue_filters(): void
    {
        $admin = User::factory()->administrator()->create();
        $topicEntry = QuestionImportEntry::factory()->create([
            'source_group' => 'master_parking_rules',
            'exam_family' => 'unspecified',
            'english_question' => 'Unique parking review queue question.',
            'study_topics' => [EditorialStudyTopic::ParkingAndStopping->value],
            'proposed_exam_category' => EditorialExamCategory::Unclassified->value,
            'editorial_categories' => [EditorialExamCategory::Unclassified->value],
        ]);
        QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'exam_family' => 'karimen',
            'english_question' => 'Unique karimen outside topic filter.',
            'proposed_exam_category' => EditorialExamCategory::Karimen->value,
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class)
            ->set('studyTopic', EditorialStudyTopic::ParkingAndStopping->value)
            ->assertSee('Unique parking review queue question.')
            ->assertDontSee('Unique karimen outside topic filter.')
            ->set('studyTopic', '')
            ->set('classificationState', 'unclassified_exam')
            ->assertSee('Unique parking review queue question.');

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $topicEntry])
            ->set('selectedStudyTopics', [EditorialStudyTopic::RoadSigns->value])
            ->set('approveStudyTopics', true)
            ->call('saveStudyTopics')
            ->assertHasNoErrors();

        $topicEntry->refresh();
        $this->assertSame([EditorialStudyTopic::RoadSigns->value], $topicEntry->study_topics);
        $this->assertTrue($topicEntry->study_topics_approved);
        $this->assertFalse($topicEntry->classification_approved);
        $this->assertFalse($topicEntry->is_published);
    }

    #[Test]
    public function test_translation_export_filters_preserve_english_source(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'exam_family' => 'karimen',
            'english_question' => 'Exact English export source.',
            'english_explanation' => 'Exact English export explanation.',
            'correct_answer' => true,
            'editorial_categories' => [EditorialExamCategory::Karimen->value],
            'study_topics' => [EditorialStudyTopic::GeneralTrafficRules->value],
            'sign_key' => 'sign-export-1',
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '下書き日本語',
            'explanation' => '下書き解説',
            'status' => StagedTranslationStatus::Draft,
        ]);

        $service = app(StagedTranslationExportService::class);
        $payload = $service->export(
            locales: ['ja'],
            entryIds: [$entry->id],
            examCategories: [EditorialExamCategory::Karimen->value],
            studyTopics: [EditorialStudyTopic::GeneralTrafficRules->value],
            limit: 25,
        );

        $this->assertSame(1, $payload['entry_count']);
        $this->assertSame('Exact English export source.', $payload['rows'][0]['english_question']);
        $this->assertSame('Exact English export explanation.', $payload['rows'][0]['english_explanation']);
        $this->assertTrue($payload['rows'][0]['correct_answer']);
        $this->assertTrue($payload['rows'][0]['english_source_readonly']);
        $this->assertSame('sign-export-1', $payload['rows'][0]['illustration_reference']);
        $this->assertSame(StagedTranslationStatus::Draft->value, $payload['rows'][0]['status']);

        $path = storage_path('app/menkyo-exports/test-export.json');
        $service->writeJson($path, $payload);
        $this->assertFileExists($path);

        $entry->refresh();
        $this->assertSame('Exact English export source.', $entry->english_question);
        $this->assertSame(StagedTranslationStatus::Draft, $entry->translations()->where('locale', 'ja')->first()?->status);
        $this->assertFalse($entry->is_published);
    }

    #[Test]
    public function test_duplicate_review_supports_same_different_and_further_review(): void
    {
        $admin = User::factory()->administrator()->create();
        $left = QuestionImportEntry::factory()->create([
            'english_question' => 'Cargo may be loaded on seats.',
            'correct_answer' => true,
            'duplicate_kind' => QuestionImportDuplicateKind::SimilarWordingNeedsReview,
        ]);
        $right = QuestionImportEntry::factory()->create([
            'english_question' => 'Cargo must not be loaded on seats.',
            'correct_answer' => false,
        ]);
        $left->forceFill(['duplicate_of_entry_id' => $right->id])->save();

        $review = QuestionImportDuplicateReview::factory()->create([
            'entry_id' => min($left->id, $right->id),
            'related_entry_id' => max($left->id, $right->id),
            'similarity_reason' => 'similar_wording_needs_review',
            'decision' => DuplicatePairDecision::NeedsAdditionalReview,
        ]);

        Livewire::actingAs($admin)
            ->test(DuplicatePairReview::class, ['review' => $review->id])
            ->assertSee('Cargo may be loaded on seats.')
            ->assertSee('Cargo must not be loaded on seats.')
            ->set('decision', DuplicatePairDecision::DifferentMeaning->value)
            ->set('notes', 'Opposite loading rule.')
            ->call('saveDecision')
            ->assertHasNoErrors();

        $this->assertSame(DuplicatePairDecision::DifferentMeaning, $review->fresh()->decision);
        $this->assertDatabaseCount('question_import_entries', 2);
        $this->assertSame('Cargo may be loaded on seats.', $left->fresh()->english_question);
        $this->assertTrue($left->fresh()->correct_answer);
        $this->assertFalse($right->fresh()->correct_answer);
    }

    #[Test]
    public function test_bulk_topic_mark_review_and_dashboard_do_not_publish(): void
    {
        $admin = User::factory()->administrator()->create();
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'master_speed_limits',
            'exam_family' => 'unspecified',
            'english_question' => 'Bulk topic mark review question.',
            'is_published' => false,
        ]);
        $demo = Question::factory()->create([
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
            'japanese_text' => '既存デモ問題',
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class)
            ->call('toggleSelect', $entry->id)
            ->set('bulkTopic', EditorialStudyTopic::SpeedLimits->value)
            ->set('bulkNotes', 'Needs native-speaker check.')
            ->set('confirmBulkAction', true)
            ->call('bulkAssignTopics')
            ->assertHasNoErrors()
            ->call('toggleSelect', $entry->id)
            ->set('confirmBulkAction', true)
            ->call('bulkMarkReview')
            ->assertHasNoErrors();

        $entry->refresh();
        $this->assertSame([EditorialStudyTopic::SpeedLimits->value], $entry->study_topics);
        $this->assertSame(EditorialWorkflowStatus::NeedsReview, $entry->editorial_workflow_status);
        $this->assertFalse($entry->is_published);
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertSame('既存デモ問題', $demo->fresh()->japanese_text);

        Livewire::actingAs($admin)
            ->test(QuestionBankDashboard::class)
            ->assertOk()
            ->assertSee(__('admin.classification_progress', [], 'ja'))
            ->call('generateProposals')
            ->assertHasNoErrors();

        $this->assertGreaterThan(0, QuestionImportReviewAudit::query()->count());
    }
}
