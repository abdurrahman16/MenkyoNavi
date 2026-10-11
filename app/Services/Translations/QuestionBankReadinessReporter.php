<?php

namespace App\Services\Translations;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * @phpstan-type ReadinessReport array<string, mixed>
 */
class QuestionBankReadinessReporter
{
    public function __construct(
        private HonmenIllustrationAuditor $honmenAuditor,
    ) {}

    /**
     * @return ReadinessReport
     */
    public function report(): array
    {
        $stagedTotal = QuestionImportEntry::query()->count();
        $locales = SupportedLocale::codes();

        $translationsByLocale = [];
        $draftByLocale = [];
        $reviewedByLocale = [];

        foreach ($locales as $locale) {
            $translationsByLocale[$locale] = QuestionImportEntryTranslation::query()
                ->where('locale', $locale)
                ->whereNotNull('question_text')
                ->where('question_text', '!=', '')
                ->count();

            $draftByLocale[$locale] = QuestionImportEntryTranslation::query()
                ->where('locale', $locale)
                ->where('status', StagedTranslationStatus::Draft)
                ->count();

            $reviewedByLocale[$locale] = QuestionImportEntryTranslation::query()
                ->where('locale', $locale)
                ->whereIn('status', [
                    StagedTranslationStatus::Reviewed->value,
                    StagedTranslationStatus::RegulatoryVerificationPending->value,
                    StagedTranslationStatus::ReadyForPublication->value,
                ])
                ->count();
        }

        $answerKeyCounts = [];

        foreach (AnswerKeyReviewStatus::cases() as $status) {
            $answerKeyCounts[$status->value] = QuestionImportEntry::query()
                ->where('answer_key_status', $status)
                ->count();
        }

        $rightsCounts = [];

        foreach (ContentRightsReviewStatus::cases() as $status) {
            $rightsCounts[$status->value] = QuestionImportEntry::query()
                ->where('content_rights_status', $status)
                ->count();
        }

        $publicationReady = 0;
        $blocked = 0;

        QuestionImportEntry::query()
            ->with('translations')
            ->orderBy('id')
            ->chunkById(100, function ($entries) use (&$publicationReady, &$blocked): void {
                foreach ($entries as $entry) {
                    if ($entry->isPublicationReady()) {
                        $publicationReady++;
                    } else {
                        $blocked++;
                    }
                }
            });

        $publishedQuestions = Question::query()
            ->where('status', QuestionStatus::Published)
            ->where('is_demo', false)
            ->count();

        $draftPromoted = QuestionImportEntry::query()
            ->whereNotNull('promoted_question_id')
            ->count();

        $examAvailability = [];

        foreach (ExamType::query()->where('is_active', true)->get() as $examType) {
            $examAvailability[$examType->slug] = [
                'published_standard' => $examType->questions()
                    ->where('type', 'standard_true_false')
                    ->where('status', QuestionStatus::Published)
                    ->where('is_demo', false)
                    ->count(),
                'published_illustrated' => $examType->questions()
                    ->where('type', 'illustrated_group')
                    ->where('status', QuestionStatus::Published)
                    ->where('is_demo', false)
                    ->count(),
                'required_standard' => $examType->standardQuestionCount(),
                'required_illustrated' => $examType->illustratedGroupCount(),
            ];
        }

        $honmen = $this->honmenAuditor->audit();
        $sourceAudit = $this->sourceFileAudit();
        $perSource = $this->entriesPerSource();
        $duplicateCounts = $this->duplicateCounts();

        $uniqueContentFingerprints = (int) DB::table('question_import_entries')
            ->distinct()
            ->count('content_fingerprint');

        $bankReady = $publicationReady > 0
            && $publishedQuestions >= 50
            && ($examAvailability['karimen']['published_standard'] ?? 0) >= 50
            && ($honmen['mock_exam_ready'] ?? false);

        return [
            'staged_questions_total' => $stagedTotal,
            'staged_entries_per_source' => $perSource,
            'unique_content_fingerprints' => $uniqueContentFingerprints,
            'duplicate_counts' => $duplicateCounts,
            'conflicting_answer_count' => ($duplicateCounts['exact_question_different_answer'] ?? 0)
                + ($duplicateCounts['same_wording_different_answer'] ?? 0),
            'translations_with_text_by_locale' => $translationsByLocale,
            'draft_translations_by_locale' => $draftByLocale,
            'reviewed_translations_by_locale' => $reviewedByLocale,
            'answer_key_verification' => $answerKeyCounts,
            'content_rights_approval' => $rightsCounts,
            'publication_ready_questions' => $publicationReady,
            'questions_blocked_from_publishing' => $blocked,
            'draft_promoted_questions' => $draftPromoted,
            'published_non_demo_questions' => $publishedQuestions,
            'available_by_exam_type' => $examAvailability,
            'honmen_audit' => $honmen,
            'missing_illustrated_groups' => max(
                0,
                ($honmen['engine_requires_illustrated_groups'] ?? 0) - ($honmen['illustrated_group_records_found'] ?? 0),
            ),
            'full_mock_requirements_met' => (bool) ($honmen['mock_exam_ready'] ?? false)
                && (($examAvailability['karimen']['published_standard'] ?? 0) >= ($examAvailability['karimen']['required_standard'] ?? 50)),
            'source_file_audit' => $sourceAudit,
            'question_bank_ready_for_public_exams' => $bankReady,
            'disclaimer' => 'Staged import count alone does not mean the question bank is publication-ready. Total source entries are distinct from unique reviewed questions.',
        ];
    }

    /**
     * @return array<string, int>
     */
    private function entriesPerSource(): array
    {
        $counts = [];

        foreach (QuestionImportBatch::query()->orderBy('id')->get() as $batch) {
            $counts[$batch->source_key] = QuestionImportEntry::query()
                ->where('question_import_batch_id', $batch->id)
                ->count();
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    private function duplicateCounts(): array
    {
        $counts = [];

        foreach (QuestionImportDuplicateKind::cases() as $kind) {
            if ($kind === QuestionImportDuplicateKind::None) {
                continue;
            }

            $counts[$kind->value] = QuestionImportEntry::query()
                ->where('duplicate_kind', $kind)
                ->count();
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceFileAudit(): array
    {
        $tupleStorage = storage_path('app/menkyo-imports/karimen_exam_trainer (1).html');
        $objectStorage = storage_path('app/menkyo-imports/karimen_exam_trainer.html');
        $fixtureTuple = base_path('tests/Fixtures/Imports/karimen_exam_trainer.html');
        $fixtureObject = base_path('tests/Fixtures/Imports/karimen_exam_trainer_object.html');
        $publicTuple = public_path('menkyo-imports/karimen_exam_trainer (1).html');
        $publicObject = public_path('menkyo-imports/karimen_exam_trainer.html');

        return [
            'storage_source_exists' => is_file($tupleStorage),
            'storage_object_source_exists' => is_file($objectStorage),
            'storage_source_path' => 'storage/app/menkyo-imports/karimen_exam_trainer (1).html',
            'storage_object_source_path' => 'storage/app/menkyo-imports/karimen_exam_trainer.html',
            'storage_outside_public' => ! is_file($publicTuple) && ! is_file($publicObject),
            'fixture_full_html_exists' => is_file($fixtureTuple),
            'fixture_object_html_exists' => is_file($fixtureObject),
            'fixture_full_html_gitignored' => is_file(base_path('tests/Fixtures/Imports/.gitignore'))
                && str_contains((string) File::get(base_path('tests/Fixtures/Imports/.gitignore')), 'karimen_exam_trainer.html'),
            'tuple_checksum' => is_file($tupleStorage) ? hash_file('sha256', $tupleStorage) : null,
            'object_checksum' => is_file($objectStorage) ? hash_file('sha256', $objectStorage) : null,
            'git_repository_present' => is_dir(base_path('.git')),
            'notes' => [
                'Keep original HTML files outside public/ and avoid committing copyrighted question banks to a public remote.',
                'Local original materials must be preserved; do not delete user source files.',
                'If two HTML sources disagree, report conflicts — do not rewrite either file.',
            ],
        ];
    }
}
