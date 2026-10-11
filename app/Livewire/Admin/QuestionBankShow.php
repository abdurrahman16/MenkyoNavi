<?php

namespace App\Livewire\Admin;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialExamCategory;
use App\Enums\EditorialIssueType;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\FigureReviewStatus;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Editorial\KarimenTest1EvidenceLedger;
use App\Services\Editorial\KarimenTest1SignAssetResolver;
use App\Services\Editorial\OriginalKarimenPilotAccuracyAudit;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Translations\StagedQuestionPublicationService;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;

#[Layout('layouts.app')]
class QuestionBankShow extends Component
{
    use WithFileUploads;

    public QuestionImportEntry $entry;

    /** @var list<string> */
    public array $selectedCategories = [];

    public bool $approveClassification = false;

    /** @var list<string> */
    public array $selectedStudyTopics = [];

    public bool $approveStudyTopics = false;

    public string $answerKeyStatus = '';

    public string $answerKeyNotes = '';

    public string $answerKeyReference = '';

    public string $proposedCorrectAnswer = '';

    public string $proposedAnswerNotes = '';

    public string $contentRightsStatus = '';

    public string $contentRightsNotes = '';

    public string $contentRightsEvidence = '';

    public string $accuracyEvidenceStatus = '';

    public string $legalTopic = '';

    public string $accuracyVerificationNotes = '';

    public string $accuracyEvidenceSourcesJson = '';

    public string $figureReviewStatus = '';

    public string $figureReviewNotes = '';

    /** @var list<string> */
    public array $selectedIssues = [];

    public string $editorialNotes = '';

    public string $workflowStatus = '';

    public string $statusMessage = '';

    public string $batchContentRightsStatus = '';

    public string $batchClaimedCreator = '';

    public string $batchOriginUrl = '';

    public string $batchAcquisitionNotes = '';

    public string $batchLicenseNotes = '';

    public string $batchPermissionNotes = '';

    public string $batchReuseScopeNotes = '';

    public string $batchFigureRightsNotes = '';

    public string $batchRightsEvidenceJson = '';

    /** @var TemporaryUploadedFile|null */
    public $batchRightsDocument = null;

    public function mount(QuestionImportEntry $entry): void
    {
        $this->authorize('view', $entry);
        $this->entry = $entry->loadMissing([
            'batch',
            'translations',
            'reviewAudits.user',
            'classificationApprover',
            'contentRightsReviewer',
            'duplicateOf.batch',
        ]);
        $this->hydrateForm();
    }

    public function saveClassification(EditorialReviewService $editorial): void
    {
        $this->authorize('classify', $this->entry);

        $validated = $this->validate([
            'selectedCategories' => ['required', 'array', 'min:1'],
            'selectedCategories.*' => ['string', Rule::enum(EditorialExamCategory::class)],
            'approveClassification' => ['boolean'],
        ]);

        $this->entry = $editorial->updateClassification(
            $this->entry,
            $this->admin(),
            $validated['selectedCategories'],
            (bool) $validated['approveClassification'],
        );

        $this->hydrateForm();
        $this->statusMessage = __('admin.classification_saved');
    }

    public function saveStudyTopics(EditorialReviewService $editorial): void
    {
        $this->authorize('classify', $this->entry);

        $validated = $this->validate([
            'selectedStudyTopics' => ['required', 'array', 'min:1'],
            'selectedStudyTopics.*' => ['string', Rule::enum(EditorialStudyTopic::class)],
            'approveStudyTopics' => ['boolean'],
        ]);

        $this->entry = $editorial->updateStudyTopics(
            $this->entry,
            $this->admin(),
            $validated['selectedStudyTopics'],
            (bool) $validated['approveStudyTopics'],
        );

        $this->hydrateForm();
        $this->statusMessage = __('admin.study_topics_saved');
    }

    public function saveAnswerKeyReview(EditorialReviewService $editorial): void
    {
        $this->authorize('updateReview', $this->entry);

        $validated = $this->validate([
            'answerKeyStatus' => ['required', Rule::enum(AnswerKeyReviewStatus::class)],
            'answerKeyNotes' => ['nullable', 'string', 'max:5000'],
            'answerKeyReference' => ['nullable', 'string', 'max:2000'],
            'proposedCorrectAnswer' => ['nullable', 'in:,0,1'],
            'proposedAnswerNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $proposed = $validated['proposedCorrectAnswer'] === ''
            ? null
            : $validated['proposedCorrectAnswer'] === '1';

        $this->entry = $editorial->updateAnswerKeyReview(
            $this->entry,
            $this->admin(),
            AnswerKeyReviewStatus::from($validated['answerKeyStatus']),
            $validated['answerKeyNotes'] ?: null,
            $validated['answerKeyReference'] ?: null,
            $proposed,
            $validated['proposedAnswerNotes'] ?: null,
        );

        $this->hydrateForm();
        $this->statusMessage = 'Answer-key review saved. Original answer key remains unchanged.';
    }

    public function saveContentRights(EditorialReviewService $editorial): void
    {
        $this->authorize('updateReview', $this->entry);

        $validated = $this->validate([
            'contentRightsStatus' => ['required', Rule::enum(ContentRightsReviewStatus::class)],
            'contentRightsNotes' => ['nullable', 'string', 'max:5000'],
            'contentRightsEvidence' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->entry = $editorial->updateContentRights(
            $this->entry,
            $this->admin(),
            ContentRightsReviewStatus::from($validated['contentRightsStatus']),
            $validated['contentRightsNotes'] ?: null,
            $validated['contentRightsEvidence'] ?: null,
        );

        $this->hydrateForm();
        $this->statusMessage = 'Content-rights review saved.';
    }

    public function saveBatchContentRights(EditorialReviewService $editorial): void
    {
        $batch = $this->entry->batch;

        abort_unless($batch !== null, 404);
        $this->authorize('viewBatchRights', $this->entry);

        $validated = $this->validate([
            'batchContentRightsStatus' => ['required', Rule::enum(BatchContentRightsStatus::class)],
            'batchClaimedCreator' => ['nullable', 'string', 'max:500'],
            'batchOriginUrl' => ['nullable', 'string', 'max:2000'],
            'batchAcquisitionNotes' => ['nullable', 'string', 'max:8000'],
            'batchLicenseNotes' => ['nullable', 'string', 'max:8000'],
            'batchPermissionNotes' => ['nullable', 'string', 'max:8000'],
            'batchReuseScopeNotes' => ['nullable', 'string', 'max:8000'],
            'batchFigureRightsNotes' => ['nullable', 'string', 'max:8000'],
            'batchRightsEvidenceJson' => ['nullable', 'string', 'max:50000'],
            'batchRightsDocument' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,txt', 'max:5120'],
        ]);

        $rightsEvidence = $this->decodeRightsEvidenceJson($validated['batchRightsEvidenceJson'] ?? '');

        $batch = $editorial->updateBatchContentRights(
            $batch,
            $this->admin(),
            BatchContentRightsStatus::from($validated['batchContentRightsStatus']),
            [
                'claimed_creator' => $validated['batchClaimedCreator'] ?: null,
                'origin_url' => $validated['batchOriginUrl'] ?: null,
                'acquisition_notes' => $validated['batchAcquisitionNotes'] ?: null,
                'license_notes' => $validated['batchLicenseNotes'] ?: null,
                'permission_notes' => $validated['batchPermissionNotes'] ?: null,
                'reuse_scope_notes' => $validated['batchReuseScopeNotes'] ?: null,
                'figure_rights_notes' => $validated['batchFigureRightsNotes'] ?: null,
                'rights_evidence' => $rightsEvidence,
            ],
            $this->batchRightsDocument,
        );

        $this->entry->setRelation('batch', $batch);
        $this->batchRightsDocument = null;
        $this->hydrateBatchRightsForm();
        $this->statusMessage = __('admin.batch_content_rights_saved');
    }

    public function downloadRightsDocument(): Response
    {
        $this->authorize('viewBatchRights', $this->entry);

        $batch = $this->entry->batch;
        abort_unless($batch !== null && filled($batch->rights_document_path), 404);
        abort_unless(Storage::disk('local')->exists($batch->rights_document_path), 404);

        return Storage::disk('local')->download(
            $batch->rights_document_path,
            $batch->rights_document_original_name ?? 'batch-rights-document',
        );
    }

    public function saveAccuracyEvidence(EditorialReviewService $editorial): void
    {
        $this->authorize('updateReview', $this->entry);

        $validated = $this->validate([
            'accuracyEvidenceStatus' => ['required', Rule::enum(AccuracyEvidenceStatus::class)],
            'legalTopic' => ['nullable', 'string', 'max:500'],
            'accuracyVerificationNotes' => ['nullable', 'string', 'max:8000'],
            'accuracyEvidenceSourcesJson' => ['nullable', 'string', 'max:50000'],
        ]);

        $sources = $this->decodeSourcesJson($validated['accuracyEvidenceSourcesJson'] ?? '');

        $this->entry = $editorial->updateAccuracyEvidence(
            $this->entry,
            $this->admin(),
            AccuracyEvidenceStatus::from($validated['accuracyEvidenceStatus']),
            filled($validated['legalTopic'] ?? '') ? $validated['legalTopic'] : null,
            $sources,
            filled($validated['accuracyVerificationNotes'] ?? '') ? $validated['accuracyVerificationNotes'] : null,
        );

        $this->hydrateForm();
        $this->statusMessage = __('admin.accuracy_evidence_saved');
    }

    public function saveFigureReview(EditorialReviewService $editorial): void
    {
        $this->authorize('updateReview', $this->entry);

        $validated = $this->validate([
            'figureReviewStatus' => ['required', Rule::enum(FigureReviewStatus::class)],
            'figureReviewNotes' => ['nullable', 'string', 'max:8000'],
        ]);

        $this->entry = $editorial->updateFigureReview(
            $this->entry,
            $this->admin(),
            FigureReviewStatus::from($validated['figureReviewStatus']),
            filled($validated['figureReviewNotes'] ?? '') ? $validated['figureReviewNotes'] : null,
        );

        $this->hydrateForm();
        $this->statusMessage = __('admin.figure_review_saved');
    }

    public function saveEditorialIssues(EditorialReviewService $editorial): void
    {
        $this->authorize('updateReview', $this->entry);

        $validated = $this->validate([
            'selectedIssues' => ['array'],
            'selectedIssues.*' => ['string', Rule::enum(EditorialIssueType::class)],
            'editorialNotes' => ['nullable', 'string', 'max:8000'],
            'workflowStatus' => ['required', Rule::enum(EditorialWorkflowStatus::class)],
        ]);

        $this->entry = $editorial->updateEditorialIssues(
            $this->entry,
            $this->admin(),
            $validated['selectedIssues'],
            $validated['editorialNotes'] ?: null,
            EditorialWorkflowStatus::from($validated['workflowStatus']),
        );

        $this->hydrateForm();
        $this->statusMessage = 'Editorial issues and workflow updated.';
    }

    public function publishToLivePool(StagedQuestionPublicationService $publication): void
    {
        $this->authorize('updateReview', $this->entry);

        $result = $publication->publishToLivePool($this->entry->fresh(['translations', 'batch']), $this->admin());
        $this->hydrateForm();

        if ($result['published']) {
            $this->statusMessage = __('admin.publish_to_live_pool_success', [
                'id' => (string) $result['question_id'],
            ]);

            return;
        }

        $this->statusMessage = __('admin.publish_to_live_pool_blocked', [
            'blockers' => implode(', ', $result['blockers']),
        ]);
    }

    public function render(): View
    {
        $ledger = app(KarimenTest1EvidenceLedger::class);
        $signResolver = app(KarimenTest1SignAssetResolver::class);
        $isKarimenTest1 = $this->entry->source_group === KarimenTest1EvidenceLedger::SOURCE_GROUP;
        $evidenceLedger = $isKarimenTest1 ? $ledger->catalogFor($this->entry) : null;
        $batchRights = $isKarimenTest1 ? $ledger->batchRightsSnapshot($this->entry->batch) : null;
        $signAsset = $isKarimenTest1 ? $signResolver->resolve($this->entry) : null;
        $figureSvgPreview = null;

        if ($signAsset !== null && filled($signAsset['svg_markup'])) {
            $figureSvgPreview = $signResolver->sanitizeSvgMarkup((string) $signAsset['svg_markup']);
        }

        $blockers = $this->entry->computePublicationBlockers();

        $pilotTranslationDrafts = app(OriginalKarimenPilotTranslationDraftService::class);
        $isOriginalPilot = $this->entry->source_group === OriginalKarimenPilotTranslationDraftService::SOURCE_GROUP;
        $pilotWithheld = null;
        $pilotSourceFingerprint = null;
        $pilotResearchAssessment = null;

        if ($isOriginalPilot) {
            $pilotTranslationDrafts->refreshStaleTranslationFlags($this->entry);
            $this->entry->refresh()->loadMissing('translations');
            $pilotSourceFingerprint = $pilotTranslationDrafts->sourceContentFingerprint($this->entry);
            $number = (int) $this->entry->original_question_number;
            $pilotResearchAssessment = data_get($this->entry->source_metadata, 'research_assessment')
                ?: (app(OriginalKarimenPilotAccuracyAudit::class)->forNumber($number)['research_assessment'] ?? null);
            $pilotWithheld = collect($pilotTranslationDrafts->withheldCandidates())
                ->firstWhere('number', $number);
        }

        return view('livewire.admin.question-bank-show', [
            'categories' => EditorialExamCategory::cases(),
            'studyTopics' => EditorialStudyTopic::cases(),
            'answerStatuses' => AnswerKeyReviewStatus::cases(),
            'rightsStatuses' => ContentRightsReviewStatus::cases(),
            'batchRightsStatuses' => BatchContentRightsStatus::cases(),
            'accuracyStatuses' => AccuracyEvidenceStatus::cases(),
            'figureStatuses' => FigureReviewStatus::cases(),
            'issueTypes' => EditorialIssueType::cases(),
            'workflows' => EditorialWorkflowStatus::cases(),
            'locales' => SupportedLocale::codes(),
            'publicationReady' => $this->entry->isPublicationReady(),
            'blockers' => $blockers,
            'evidenceLedger' => $evidenceLedger,
            'batchRights' => $batchRights,
            'signAsset' => $signAsset,
            'figureSvgPreview' => $figureSvgPreview,
            'readinessSummary' => [
                'publication_ready' => $this->entry->isPublicationReady(),
                'blockers' => $blockers,
            ],
            'isOriginalPilot' => $isOriginalPilot,
            'pilotWithheld' => $pilotWithheld,
            'pilotSourceFingerprint' => $pilotSourceFingerprint,
            'pilotResearchAssessment' => $pilotResearchAssessment,
            'pilotTranslationDrafts' => $pilotTranslationDrafts,
        ])->title('Review #'.$this->entry->id);
    }

    private function admin(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function hydrateForm(): void
    {
        $this->entry->refresh()->loadMissing([
            'batch',
            'translations',
            'reviewAudits.user',
            'classificationApprover',
            'contentRightsReviewer',
            'duplicateOf.batch',
        ]);

        $this->selectedCategories = $this->entry->editorial_categories
            ?? [EditorialExamCategory::proposeFromSourceExamFamily($this->entry->exam_family)->value];
        $this->approveClassification = (bool) $this->entry->classification_approved;
        $this->selectedStudyTopics = $this->entry->study_topics
            ?? array_map(
                static fn (EditorialStudyTopic $topic): string => $topic->value,
                EditorialStudyTopic::proposeFromSourceGroup(
                    $this->entry->source_group,
                    $this->entry->source_metadata,
                ),
            );
        $this->approveStudyTopics = (bool) $this->entry->study_topics_approved;
        $this->answerKeyStatus = $this->entry->answer_key_status->value;
        $this->answerKeyNotes = (string) ($this->entry->answer_key_notes ?? '');
        $this->answerKeyReference = (string) ($this->entry->answer_key_reference ?? '');
        $this->proposedCorrectAnswer = $this->entry->proposed_correct_answer === null
            ? ''
            : ($this->entry->proposed_correct_answer ? '1' : '0');
        $this->proposedAnswerNotes = (string) ($this->entry->proposed_answer_notes ?? '');
        $this->contentRightsStatus = $this->entry->content_rights_status->value;
        $this->contentRightsNotes = (string) ($this->entry->content_rights_notes ?? '');
        $this->contentRightsEvidence = (string) ($this->entry->content_rights_evidence ?? '');
        $this->accuracyEvidenceStatus = $this->entry->accuracy_evidence_status->value;
        $this->legalTopic = (string) ($this->entry->legal_topic ?? '');
        $this->accuracyVerificationNotes = (string) ($this->entry->accuracy_verification_notes ?? '');
        $sources = $this->entry->accuracy_evidence_sources;
        $this->accuracyEvidenceSourcesJson = $sources === null
            ? ''
            : json_encode($sources, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->figureReviewStatus = $this->entry->figure_review_status->value;
        $this->figureReviewNotes = (string) ($this->entry->figure_review_notes ?? '');
        $this->selectedIssues = $this->entry->editorial_issues ?? [];
        $this->editorialNotes = (string) ($this->entry->editorial_notes ?? '');
        $this->workflowStatus = $this->entry->editorial_workflow_status->value;
        $this->hydrateBatchRightsForm();
    }

    private function hydrateBatchRightsForm(): void
    {
        $batch = $this->entry->batch;

        if ($batch === null) {
            $this->batchContentRightsStatus = BatchContentRightsStatus::NoEvidenceSubmitted->value;

            return;
        }

        $status = $batch->content_rights_status instanceof BatchContentRightsStatus
            ? $batch->content_rights_status
            : BatchContentRightsStatus::tryFrom((string) $batch->content_rights_status)
                ?? BatchContentRightsStatus::NoEvidenceSubmitted;

        $this->batchContentRightsStatus = $status->value;
        $this->batchClaimedCreator = (string) ($batch->claimed_creator ?? '');
        $this->batchOriginUrl = (string) ($batch->origin_url ?? '');
        $this->batchAcquisitionNotes = (string) ($batch->acquisition_notes ?? '');
        $this->batchLicenseNotes = (string) ($batch->license_notes ?? '');
        $this->batchPermissionNotes = (string) ($batch->permission_notes ?? '');
        $this->batchReuseScopeNotes = (string) ($batch->reuse_scope_notes ?? '');
        $this->batchFigureRightsNotes = (string) ($batch->figure_rights_notes ?? '');
        $evidence = $batch->rights_evidence;
        $this->batchRightsEvidenceJson = $evidence === null
            ? ''
            : json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function decodeRightsEvidenceJson(string $raw): ?array
    {
        if (trim($raw) === '') {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'batchRightsEvidenceJson' => __('admin.batch_rights_evidence_invalid_json'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'batchRightsEvidenceJson' => __('admin.batch_rights_evidence_must_be_array'),
            ]);
        }

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function decodeSourcesJson(string $raw): ?array
    {
        if (trim($raw) === '') {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'accuracyEvidenceSourcesJson' => __('admin.accuracy_evidence_sources_invalid_json'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'accuracyEvidenceSourcesJson' => __('admin.accuracy_evidence_sources_must_be_array'),
            ]);
        }

        return $decoded;
    }
}
