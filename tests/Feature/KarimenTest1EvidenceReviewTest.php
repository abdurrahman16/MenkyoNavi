<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Editorial\KarimenTest1AuditService;
use App\Services\Editorial\KarimenTest1EvidenceLedger;
use App\Services\Translations\StagedQuestionPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KarimenTest1EvidenceReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<QuestionImportEntry>
     */
    private function seedKarimenTest1Set(int $count = 50): array
    {
        fake()->unique(true);

        $entries = [];

        for ($number = 1; $number <= $count; $number++) {
            $entries[] = QuestionImportEntry::factory()->create([
                'source_group' => 'karimen_test_1',
                'original_question_number' => $number,
                'english_question' => "Karimen test 1 question {$number} must not be rewritten.",
                'english_explanation' => "Explanation {$number} stays immutable.",
                'correct_answer' => $number % 2 === 0,
                'figure_caption' => in_array($number, [2, 28], true) ? "Figure {$number}" : null,
                'sign_key' => in_array($number, [2, 28], true) ? "sign{$number}" : null,
                'review_flags' => in_array($number, [4, 23], true) ? ['ambiguous_terminology'] : [],
                'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
                'content_rights_status' => ContentRightsReviewStatus::Unclear,
                'accuracy_evidence_status' => AccuracyEvidenceStatus::NotChecked,
                'figure_review_status' => in_array($number, [2, 28], true)
                    ? FigureReviewStatus::Pending
                    : FigureReviewStatus::NotApplicable,
                'is_published' => false,
                'source_entry_checksum' => hash('sha256', 'karimen_test_1|'.$number),
            ]);
        }

        foreach ([1, 2, 19, 28, 38] as $number) {
            $entry = collect($entries)->firstWhere('original_question_number', $number);
            QuestionImportEntryTranslation::factory()->create([
                'question_import_entry_id' => $entry->id,
                'locale' => 'ja',
                'question_text' => "日本語下書き {$number}",
                'explanation' => "解説 {$number}",
                'figure_caption' => $entry->figure_caption ? "図 {$number}" : null,
                'status' => StagedTranslationStatus::Draft,
            ]);
        }

        return $entries;
    }

    #[Test]
    public function test_audit_scopes_exactly_fifty_questions_and_keeps_imported_answers_immutable(): void
    {
        $this->seedKarimenTest1Set(50);
        fake()->unique(true);
        QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_2',
            'original_question_number' => 1,
        ]);

        $before = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->orderBy('original_question_number')
            ->get(['english_question', 'correct_answer', 'english_explanation', 'source_entry_checksum', 'is_published']);

        $audit = app(KarimenTest1AuditService::class)->audit();

        $this->assertSame(2, $audit['format_version']);
        $this->assertSame(50, $audit['task_32_state']['total_questions']);
        $this->assertSame(50, count($audit['items']));
        $this->assertSame('not_cleared', $audit['batch_rights']['status']);
        $this->assertTrue($audit['disclaimer']['task_32_evidence_collected_is_not_verified']);

        $after = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->orderBy('original_question_number')
            ->get(['english_question', 'correct_answer', 'english_explanation', 'source_entry_checksum', 'is_published']);

        $this->assertSame($before->toArray(), $after->toArray());
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
    }

    #[Test]
    public function test_evidence_seed_does_not_verify_answers_or_clear_rights_or_publish(): void
    {
        $entries = $this->seedKarimenTest1Set(50);
        $admin = User::factory()->administrator()->create();
        $ledger = app(KarimenTest1EvidenceLedger::class);
        $editorial = app(EditorialReviewService::class);

        foreach ($entries as $entry) {
            $editorial->seedAccuracyEvidenceFromLedger($entry, $admin, $ledger);
        }

        $this->assertSame(0, QuestionImportEntry::query()->where('answer_key_status', AnswerKeyReviewStatus::Verified)->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('content_rights_status', ContentRightsReviewStatus::ApprovedForPublicReuse)->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('accuracy_evidence_status', AccuracyEvidenceStatus::HumanReviewed)->count());

        $q4 = QuestionImportEntry::query()->where('original_question_number', 4)->where('source_group', 'karimen_test_1')->firstOrFail();
        $this->assertSame(AccuracyEvidenceStatus::NeedsClarification, $q4->accuracy_evidence_status);

        $q38 = QuestionImportEntry::query()->where('original_question_number', 38)->where('source_group', 'karimen_test_1')->firstOrFail();
        $this->assertSame(AccuracyEvidenceStatus::ConflictingEvidence, $q38->accuracy_evidence_status);

        $q2 = QuestionImportEntry::query()->where('original_question_number', 2)->where('source_group', 'karimen_test_1')->firstOrFail();
        $this->assertTrue($q2->isFigureDependent());
        $this->assertFalse($q2->figure_review_status->isReadyForPublication());
        $this->assertContains('figure_review_'.$q2->figure_review_status->value, $q2->computePublicationBlockers());
    }

    #[Test]
    public function test_evidence_collected_does_not_confer_publication_readiness(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 7,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::EvidenceCollected,
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'content_rights_status' => ContentRightsReviewStatus::ApprovedForPublicReuse,
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'review_flags' => [],
            'editorial_issues' => [],
            'is_published' => false,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '完了した日本語',
            'explanation' => '解説',
            'status' => StagedTranslationStatus::ReadyForPublication,
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'en',
            'question_text' => 'English record',
            'explanation' => 'Explain',
            'status' => StagedTranslationStatus::ReadyForPublication,
        ]);

        $blockers = $entry->fresh()->load('translations')->computePublicationBlockers();
        $this->assertContains('accuracy_evidence_evidence_collected', $blockers);
        $this->assertFalse($entry->fresh()->isPublicationReady());

        $result = app(StagedQuestionPromotionService::class)->promoteToDraft($entry->fresh()->load('translations'));
        $this->assertFalse($result['promoted']);
        $this->assertContains('accuracy_evidence_evidence_collected', $result['blockers']);
    }

    #[Test]
    public function test_figure_dependent_question_cannot_be_ready_without_figure_review(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 2,
            'sign_key' => 'pedbike',
            'figure_caption' => 'Pedestrian + bicycle crossing sign',
            'figure_review_status' => FigureReviewStatus::FigureMissing,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'content_rights_status' => ContentRightsReviewStatus::ApprovedForPublicReuse,
            'review_flags' => [],
            'editorial_issues' => [],
        ]);

        $this->assertContains('figure_review_figure_missing', $entry->computePublicationBlockers());
    }

    #[Test]
    public function test_admin_evidence_endpoints_require_authorization_and_preserve_audits(): void
    {
        $entries = $this->seedKarimenTest1Set(50);
        $entry = collect($entries)->firstWhere('original_question_number', 19);
        $guest = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->administrator()->create();

        $this->actingAs($guest)
            ->get(route('admin.questions.show', $entry))
            ->assertForbidden();

        Livewire::actingAs($guest)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->assertForbidden();

        $sources = json_encode([[
            'title' => '道路交通法',
            'organization' => 'e-Gov 法令検索',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105',
            'section' => '第87条',
            'checked_at' => '2026-10-10',
            'claim_supported' => 'Learner plate front and rear',
            'relationship' => 'supports',
            'uncertainty' => 'Human confirmation still required',
        ]], JSON_UNESCAPED_UNICODE);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->assertSee('data-evidence-review-panel', false)
            ->set('accuracyEvidenceStatus', AccuracyEvidenceStatus::EvidenceCollected->value)
            ->set('legalTopic', 'Learner permit practice plate')
            ->set('accuracyEvidenceSourcesJson', $sources)
            ->set('accuracyVerificationNotes', 'Catalog source recorded; not human-verified.')
            ->call('saveAccuracyEvidence')
            ->assertHasNoErrors();

        $entry->refresh();
        $this->assertSame(AccuracyEvidenceStatus::EvidenceCollected, $entry->accuracy_evidence_status);
        $this->assertSame(AnswerKeyReviewStatus::VerificationPending, $entry->answer_key_status);
        $this->assertSame('Karimen test 1 question 19 must not be rewritten.', $entry->english_question);
        $this->assertFalse($entry->correct_answer);

        $this->assertDatabaseHas('question_import_review_audits', [
            'question_import_entry_id' => $entry->id,
            'user_id' => $admin->id,
            'action' => 'accuracy_evidence_updated',
        ]);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('accuracyEvidenceStatus', AccuracyEvidenceStatus::HumanReviewed->value)
            ->set('accuracyEvidenceSourcesJson', '')
            ->set('accuracyVerificationNotes', '')
            ->call('saveAccuracyEvidence')
            ->assertHasErrors('accuracy_verification_notes');
    }

    #[Test]
    public function test_private_reports_are_not_publicly_downloadable_and_counts_match_database(): void
    {
        $this->seedKarimenTest1Set(50);
        $admin = User::factory()->administrator()->create();
        $ledger = app(KarimenTest1EvidenceLedger::class);
        $editorial = app(EditorialReviewService::class);

        foreach (QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->cursor() as $entry) {
            $editorial->seedAccuracyEvidenceFromLedger($entry, $admin, $ledger);
        }

        $audit = app(KarimenTest1AuditService::class)->audit();
        $paths = app(KarimenTest1AuditService::class)->writePrivateReports(
            $audit,
            storage_path('app/menkyo-exports/test-task32-audit.json'),
            storage_path('app/menkyo-exports/test-task32-audit.md'),
        );

        $this->assertFileExists($paths['json_path']);
        $this->assertStringContainsString('menkyo-exports', $paths['json_path']);
        $this->assertStringNotContainsString('/public/', str_replace('\\', '/', $paths['json_path']));

        $publicLeak = $this->get('/storage/menkyo-exports/test-task32-audit.json');
        $this->assertContains($publicLeak->status(), [403, 404]);
        $publicLeak->assertDontSee('task_32_state', false);

        $dbEvidenceCollected = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->where('accuracy_evidence_status', AccuracyEvidenceStatus::EvidenceCollected)
            ->count();
        $this->assertSame($dbEvidenceCollected, $audit['task_32_state']['accuracy_evidence_collected']);
        $this->assertSame(50, $audit['task_32_state']['blocked_from_publication']);
        $this->assertSame(0, $audit['task_32_state']['meeting_all_recorded_criteria']);
        $this->assertSame(0, $audit['task_32_state']['rights_documented']);
        $this->assertGreaterThan(0, $audit['task_32_state']['figure_dependent']);

        $this->assertSame(
            QuestionImportReviewAudit::query()->where('action', 'accuracy_evidence_seeded_from_ledger')->count(),
            50
        );
    }

    #[Test]
    public function test_unreviewed_translations_and_wording_flags_remain_visible_in_audit(): void
    {
        $this->seedKarimenTest1Set(50);
        $audit = app(KarimenTest1AuditService::class)->audit();

        $q4 = collect($audit['items'])->firstWhere('original_question_number', 4);
        $this->assertNotEmpty($q4['database_facts']['review_flags']);
        $this->assertFalse($q4['eight_language_draft_complete']);
        $this->assertSame('not_checked', $q4['evidence_ledger']['accuracy_evidence_status']);
        $this->assertSame('needs_clarification', $q4['evidence_ledger']['catalog_suggested_accuracy_evidence_status']);

        $q23 = collect($audit['items'])->firstWhere('original_question_number', 23);
        $this->assertNotEmpty($q23['database_facts']['review_flags']);

        foreach ($audit['items'] as $item) {
            foreach ($item['translation_availability_by_locale'] as $locale => $row) {
                if (($row['status'] ?? null) === StagedTranslationStatus::Draft->value) {
                    $this->assertNotSame(StagedTranslationStatus::ReadyForPublication->value, $row['status']);
                }
            }
        }
    }
}
