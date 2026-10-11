<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <a href="{{ route('admin.questions.index') }}" class="text-sm font-medium text-navy-muted hover:text-navy">← {{ __('admin.back_to_list') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-navy">{{ __('admin.review_entry_title', ['id' => $entry->id]) }}</h1>
            <p class="mt-1 text-sm text-navy-muted">
                {{ $entry->batch?->source_key }} · {{ $entry->source_group }} #{{ $entry->original_question_number }}
            </p>
        </div>

        @if ($statusMessage)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ $statusMessage }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $isOriginalKarimenPilot = $entry->source_group === 'menkyo_original_karimen_pilot_001';
            $isOriginalKarimenExpansion = $entry->source_group === 'menkyo_original_karimen_expansion_001';
            $isOriginalJapaneseFirstParty = $isOriginalKarimenPilot || $isOriginalKarimenExpansion;
        @endphp
        @if ($entry->source_group === 'karimen_test_1' || $isOriginalJapaneseFirstParty)
            <section
                data-evidence-review-panel
                class="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-5 space-y-5"
                lang="ja"
            >
                <div>
                    <h2 class="text-lg font-semibold text-navy">
                        @if ($isOriginalKarimenExpansion)
                            {{ __('admin.original_expansion_evidence_panel_title') }}
                        @elseif ($isOriginalKarimenPilot)
                            {{ __('admin.original_pilot_evidence_panel_title') }}
                        @else
                            {{ __('admin.evidence_review_panel_title') }}
                        @endif
                    </h2>
                    <p class="mt-1 text-xs text-navy-muted">
                        @if ($isOriginalKarimenExpansion)
                            {{ __('admin.original_expansion_evidence_panel_disclaimer') }}
                        @elseif ($isOriginalKarimenPilot)
                            {{ __('admin.original_pilot_evidence_panel_disclaimer') }}
                        @else
                            {{ __('admin.evidence_review_panel_disclaimer') }}
                        @endif
                    </p>
                </div>

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">
                        {{ $isOriginalJapaneseFirstParty ? __('admin.original_pilot_source_section') : __('admin.evidence_section_original') }}
                    </h3>
                    @if ($isOriginalJapaneseFirstParty)
                        <p class="whitespace-pre-wrap text-navy">{{ $entry->translationFor('ja')?->question_text ?: '—' }}</p>
                        <p class="text-xs text-navy-muted">
                            {{ __('admin.proposed_answer') }}:
                            <span class="font-semibold text-navy">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</span>
                            <span class="ml-2">{{ __('admin.imported_answer_unverified') }}</span>
                        </p>
                        <p class="whitespace-pre-wrap text-navy-muted text-xs">{{ $entry->translationFor('ja')?->explanation ?: '—' }}</p>
                        <p class="mt-2 text-xs text-navy-muted">{{ __('admin.original_pilot_english_gloss_note') }}</p>
                        <p class="whitespace-pre-wrap text-xs text-navy-muted">{{ $entry->english_question }}</p>
                    @else
                        <p class="whitespace-pre-wrap text-navy">{{ $entry->english_question }}</p>
                        <p class="text-xs text-navy-muted">
                            {{ __('admin.imported_answer_unverified') }}:
                            <span class="font-semibold text-navy">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</span>
                        </p>
                        <p class="whitespace-pre-wrap text-navy-muted text-xs">{{ $entry->english_explanation }}</p>
                    @endif
                </div>

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">{{ __('admin.evidence_section_verification') }}</h3>
                    <dl class="grid gap-2 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium text-navy-muted">{{ __('admin.accuracy_evidence_status') }}</dt>
                            <dd>{{ $entry->accuracy_evidence_status->labelJa() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-navy-muted">{{ __('admin.legal_topic') }}</dt>
                            <dd>{{ $entry->legal_topic ?: ($evidenceLedger['legal_topic'] ?? '—') }}</dd>
                        </div>
                        @if ($isOriginalKarimenExpansion)
                            <div>
                                <dt class="text-xs font-medium text-navy-muted">{{ __('admin.expansion_batch_label') }}</dt>
                                <dd data-expansion-batch>{{ data_get($entry->source_metadata, 'expansion_batch') ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_fingerprint_label') }}</dt>
                                <dd class="break-all text-xs"><code>{{ $entry->content_fingerprint }}</code></dd>
                            </div>
                        @endif
                        @if (filled(data_get($entry->source_metadata, 'research_assessment')))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-navy-muted">{{ __('admin.research_assessment') }}</dt>
                                <dd class="font-medium text-navy">
                                    {{ data_get($entry->source_metadata, 'research_assessment') }}
                                    <span class="ml-2 text-xs font-normal text-amber-800">{{ __('admin.research_assessment_not_human_approval') }}</span>
                                </dd>
                            </div>
                        @endif
                        @if (filled($entry->answer_key_reference))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-navy-muted">{{ __('admin.answer_key_reference') }}</dt>
                                <dd>{{ $entry->answer_key_reference }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if (filled(data_get($entry->source_metadata, 'ambiguity')))
                        <p class="mt-2 text-xs text-navy-muted">
                            <span class="font-medium text-navy">{{ __('admin.ambiguity_notes') }}:</span>
                            {{ data_get($entry->source_metadata, 'ambiguity') }}
                        </p>
                    @endif
                    @if (filled(data_get($entry->source_metadata, 'proposed_question_ja')))
                        <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50/60 p-3 text-xs">
                            <p class="font-medium text-navy">{{ __('admin.proposed_question_revision') }}</p>
                            <p class="mt-1 whitespace-pre-wrap text-navy">{{ data_get($entry->source_metadata, 'proposed_question_ja') }}</p>
                            <p class="mt-1 text-amber-900">{{ __('admin.proposed_revision_not_applied') }}</p>
                        </div>
                    @endif
                    @if (filled($entry->accuracy_evidence_sources))
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                            @foreach ($entry->accuracy_evidence_sources as $source)
                                <li>
                                    {{ $source['title'] ?? $source['label'] ?? '—' }}
                                    @if (! empty($source['section']))
                                        · {{ $source['section'] }}
                                    @endif
                                    @if (! empty($source['url']))
                                        · <a href="{{ $source['url'] }}" class="text-accent underline" target="_blank" rel="noopener noreferrer">{{ __('admin.reference_url') }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @elseif (! empty($evidenceLedger['sources']))
                        <p class="text-xs text-navy-muted">{{ __('admin.catalog_suggested_sources') }}</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-xs text-navy-muted">
                            @foreach ($evidenceLedger['sources'] as $source)
                                <li>{{ $source['title'] ?? $source['label'] ?? '—' }} · {{ $source['section'] ?? '—' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (filled($entry->accuracy_verification_notes))
                        <p class="mt-2 whitespace-pre-wrap text-xs text-navy-muted">{{ $entry->accuracy_verification_notes }}</p>
                    @endif
                    @if (is_array(data_get($entry->source_metadata, 'human_review_questions')) && data_get($entry->source_metadata, 'human_review_questions') !== [])
                        <div class="mt-2">
                            <p class="text-xs font-medium text-navy">{{ __('admin.human_review_questions') }}</p>
                            <ul class="mt-1 list-disc space-y-1 pl-5 text-xs text-navy-muted">
                                @foreach (data_get($entry->source_metadata, 'human_review_questions') as $question)
                                    <li>{{ $question }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">{{ __('admin.evidence_section_figure') }}</h3>
                    <p>{{ $entry->figure_review_status->labelJa() }}</p>
                    @if (filled($entry->figure_review_notes))
                        <p class="text-xs text-navy-muted whitespace-pre-wrap">{{ $entry->figure_review_notes }}</p>
                    @elseif (! empty($evidenceLedger['figure_review_notes']))
                        <p class="text-xs text-navy-muted whitespace-pre-wrap">{{ $evidenceLedger['figure_review_notes'] }}</p>
                    @endif
                    <p class="text-xs text-navy-muted">{{ __('admin.sign_key') }}: {{ $entry->sign_key ?: '—' }} · {{ __('admin.figure_caption') }}: {{ $entry->figure_caption ?: '—' }}</p>
                    @if ($figureSvgPreview)
                        <div class="mt-3 rounded-lg border border-dashed border-border bg-surface/50 p-3">
                            <p class="text-xs font-medium text-navy-muted">{{ __('admin.figure_svg_preview_title') }}</p>
                            <p class="mt-1 text-xs text-amber-900">{{ __('admin.figure_svg_preview_disclaimer') }}</p>
                            <div
                                class="mt-3 max-h-64 overflow-auto rounded border border-border bg-white p-2"
                                sandbox=""
                                aria-label="{{ __('admin.figure_svg_preview_title') }}"
                            >
                                {!! $figureSvgPreview !!}
                            </div>
                        </div>
                    @elseif ($signAsset && filled($signAsset['sign_key']))
                        <p class="text-xs text-navy-muted">{{ __('admin.figure_svg_preview_unavailable') }} · {{ $signAsset['classification'] ?? '—' }}</p>
                    @endif
                </div>

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">{{ __('admin.evidence_section_copyright') }}</h3>
                    @if ($batchRights)
                        <p class="text-xs">
                            <span class="font-medium">{{ __('admin.batch_content_rights_status') }}:</span>
                            {{ $batchRights['content_rights_status_label'] ?? $batchRights['status'] ?? '—' }}
                        </p>
                        <p class="text-xs text-navy-muted">{{ $batchRights['note'] ?? '' }}</p>
                        <p class="text-xs text-navy-muted">{{ __('admin.content_rights_short', ['value' => $entry->content_rights_status->value]) }}</p>
                        @if (filled($batchRights['rights_document_original_name'] ?? null))
                            <button type="button" wire:click="downloadRightsDocument" class="mt-2 text-xs font-semibold text-navy underline">
                                {{ __('admin.download_batch_rights_document', ['name' => $batchRights['rights_document_original_name']]) }}
                            </button>
                        @endif
                    @endif
                </div>

                @if ($entry->batch)
                    <form wire:submit="saveBatchContentRights" class="rounded-xl border border-border bg-white p-4 space-y-3 text-sm">
                        <h3 class="font-semibold text-navy">{{ __('admin.batch_content_rights_form_title') }}</h3>
                        <p class="text-xs text-navy-muted">{{ __('admin.batch_content_rights_form_note') }}</p>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_content_rights_status') }}</span>
                            <select wire:model="batchContentRightsStatus" class="mt-1 w-full rounded-lg border-border">
                                @foreach ($batchRightsStatuses as $status)
                                    <option value="{{ $status->value }}">{{ app()->getLocale() === 'ja' ? $status->labelJa() : $status->label() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_claimed_creator') }}</span>
                            <input type="text" wire:model="batchClaimedCreator" class="mt-1 w-full rounded-lg border-border">
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_origin_url') }}</span>
                            <input type="url" wire:model="batchOriginUrl" class="mt-1 w-full rounded-lg border-border">
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_acquisition_notes') }}</span>
                            <textarea wire:model="batchAcquisitionNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_license_notes') }}</span>
                            <textarea wire:model="batchLicenseNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_permission_notes') }}</span>
                            <textarea wire:model="batchPermissionNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_reuse_scope_notes') }}</span>
                            <textarea wire:model="batchReuseScopeNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_figure_rights_notes') }}</span>
                            <textarea wire:model="batchFigureRightsNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_rights_evidence_json') }}</span>
                            <textarea wire:model="batchRightsEvidenceJson" rows="4" class="mt-1 w-full rounded-lg border-border font-mono text-xs" placeholder="[]"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.batch_rights_document_upload') }}</span>
                            <input type="file" wire:model="batchRightsDocument" accept=".pdf,.png,.jpg,.jpeg,.txt" class="mt-1 w-full text-xs">
                            <span wire:loading wire:target="batchRightsDocument" class="text-xs text-navy-muted">{{ __('admin.uploading') }}</span>
                        </label>
                        @if ($entry->batch->rights_document_original_name)
                            <p class="text-xs text-navy-muted">{{ __('admin.batch_rights_document_on_file', ['name' => $entry->batch->rights_document_original_name]) }}</p>
                        @endif
                        <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_batch_content_rights') }}</button>
                    </form>
                @endif

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">{{ __('admin.evidence_section_translations') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($locales as $locale)
                            @php($translation = $entry->translationFor($locale))
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium uppercase',
                                'bg-emerald-100 text-emerald-900' => $translation?->hasCompleteText(),
                                'bg-slate-100 text-slate-700' => $translation !== null && ! $translation->hasCompleteText(),
                                'bg-red-100 text-red-900' => $translation === null,
                            ])>
                                {{ $locale }}: {{ $translation?->hasCompleteText() ? ($translation->status->value ?? 'ok') : ($translation ? 'partial' : 'missing') }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-border/80 bg-white p-4 text-sm space-y-2">
                    <h3 class="font-semibold text-navy">{{ __('admin.evidence_section_readiness') }}</h3>
                    <p>{{ $readinessSummary['publication_ready'] ? __('admin.ready') : __('admin.blocked') }}</p>
                    @if ($readinessSummary['blockers'] !== [])
                        <ul class="list-disc pl-5 text-xs text-navy-muted">
                            @foreach ($readinessSummary['blockers'] as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <form wire:submit="saveAccuracyEvidence" class="rounded-xl border border-border bg-white p-4 space-y-3 text-sm">
                        <h3 class="font-semibold text-navy">{{ __('admin.save_accuracy_evidence') }}</h3>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.accuracy_evidence_status') }}</span>
                            <select wire:model="accuracyEvidenceStatus" class="mt-1 w-full rounded-lg border-border">
                                @foreach ($accuracyStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->labelJa() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.legal_topic') }}</span>
                            <input type="text" wire:model="legalTopic" class="mt-1 w-full rounded-lg border-border">
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.accuracy_evidence_sources_json') }}</span>
                            <textarea wire:model="accuracyEvidenceSourcesJson" rows="5" class="mt-1 w-full rounded-lg border-border font-mono text-xs" placeholder="[]"></textarea>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.accuracy_verification_notes') }}</span>
                            <textarea wire:model="accuracyVerificationNotes" rows="3" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_accuracy_evidence') }}</button>
                    </form>

                    <form wire:submit="saveFigureReview" class="rounded-xl border border-border bg-white p-4 space-y-3 text-sm">
                        <h3 class="font-semibold text-navy">{{ __('admin.save_figure_review') }}</h3>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.figure_review_status') }}</span>
                            <select wire:model="figureReviewStatus" class="mt-1 w-full rounded-lg border-border">
                                @foreach ($figureStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->labelJa() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="font-medium text-navy">{{ __('admin.figure_review_notes') }}</span>
                            <textarea wire:model="figureReviewNotes" rows="4" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_figure_review') }}</button>
                    </form>
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.english_source') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-navy-muted">{{ __('admin.english_source_immutable') }}</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-navy">{{ $entry->english_question }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-navy-muted">{{ __('admin.answer_key') }}</dt>
                    <dd class="mt-1 font-semibold text-navy">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-navy-muted">{{ __('admin.explanation') }}</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-navy">{{ $entry->english_explanation }}</dd>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-navy-muted">{{ __('admin.sign_key') }}</dt>
                        <dd class="mt-1">{{ $entry->sign_key ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-navy-muted">{{ __('admin.figure_caption') }}</dt>
                        <dd class="mt-1">{{ $entry->figure_caption ?: '—' }}</dd>
                    </div>
                </div>
                <div>
                    <dt class="font-medium text-navy-muted">{{ __('admin.import_review_flags') }}</dt>
                    <dd class="mt-1">{{ implode(', ', $entry->review_flags ?? []) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-navy-muted">{{ __('admin.publication_readiness') }}</dt>
                    <dd class="mt-1">
                        {{ $publicationReady ? __('admin.ready') : __('admin.blocked') }}
                        @if (! $publicationReady)
                            <span class="text-navy-muted">({{ implode(', ', $blockers) }})</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @unless ($entry->is_published)
                <div class="mt-4">
                    <button
                        type="button"
                        wire:click="publishToLivePool"
                        wire:confirm="{{ __('admin.publish_to_live_pool_confirm') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white"
                    >
                        {{ __('admin.publish_to_live_pool') }}
                    </button>
                    <p class="mt-2 text-xs text-navy-muted">{{ __('admin.pilot_accuracy_not_publication') }}</p>
                </div>
            @endunless
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <form wire:submit="saveClassification" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.classification') }}</h2>
                <p class="text-xs text-navy-muted">{{ __('admin.editorial_classification_note', ['family' => $entry->exam_family]) }}</p>
                @if ($entry->proposed_exam_category)
                    <p class="text-xs text-navy-muted">
                        {{ __('admin.proposed') }}: {{ $entry->proposed_exam_category }}
                        · {{ __('admin.proposal_source') }}: {{ $entry->classification_proposal_source ?? '—' }}
                    </p>
                @endif
                <div class="space-y-2">
                    @foreach ($categories as $category)
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input type="checkbox" value="{{ $category->value }}" wire:model="selectedCategories" class="rounded border-border text-accent focus:ring-accent">
                            <span>{{ $category->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <label class="flex min-h-11 items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="approveClassification" class="rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.mark_classification_approved') }}</span>
                </label>
                <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_classification') }}</button>
            </form>

            <form wire:submit="saveStudyTopics" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.study_topics') }}</h2>
                <p class="text-xs text-navy-muted">{{ __('admin.study_topics_note') }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($studyTopics as $topic)
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input type="checkbox" value="{{ $topic->value }}" wire:model="selectedStudyTopics" class="rounded border-border text-accent focus:ring-accent">
                            <span>{{ $topic->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <label class="flex min-h-11 items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="approveStudyTopics" class="rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.mark_study_topics_approved') }}</span>
                </label>
                <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_study_topics') }}</button>
            </form>

            <form wire:submit="saveEditorialIssues" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.editorial_issues_workflow') }}</h2>
                <div class="space-y-2">
                    @foreach ($issueTypes as $issue)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" value="{{ $issue->value }}" wire:model="selectedIssues" class="rounded border-border">
                            <span>{{ $issue->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.editorial_notes') }}</span>
                    <textarea wire:model="editorialNotes" rows="4" class="mt-1 w-full rounded-lg border-border"></textarea>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.workflow') }}</span>
                    <select wire:model="workflowStatus" class="mt-1 w-full rounded-lg border-border">
                        @foreach ($workflows as $workflow)
                            <option value="{{ $workflow->value }}">{{ $workflow->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_issues') }}</button>
            </form>

            <form wire:submit="saveAnswerKeyReview" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.answer_key_review') }}</h2>
                <p class="text-xs text-navy-muted">{{ __('admin.answer_key_readonly_note') }}</p>
                <p class="text-xs text-navy-muted">{{ __('admin.answer_key_evidence_checklist') }}</p>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.status') }}</span>
                    <select wire:model="answerKeyStatus" class="mt-1 w-full rounded-lg border-border">
                        @foreach ($answerStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.evidence_notes') }}</span>
                    <textarea wire:model="answerKeyNotes" rows="3" class="mt-1 w-full rounded-lg border-border"></textarea>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.reference_url') }}</span>
                    <input type="url" wire:model="answerKeyReference" class="mt-1 w-full rounded-lg border-border">
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.proposed_correction') }}</span>
                    <select wire:model="proposedCorrectAnswer" class="mt-1 w-full rounded-lg border-border">
                        <option value="">{{ __('admin.no_proposed_change') }}</option>
                        <option value="1">{{ __('admin.propose_true') }}</option>
                        <option value="0">{{ __('admin.propose_false') }}</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.proposed_correction_notes') }}</span>
                    <textarea wire:model="proposedAnswerNotes" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                </label>
                <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_answer_key_review') }}</button>
            </form>

            <form wire:submit="saveContentRights" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.content_rights_review') }}</h2>
                <p class="text-xs text-navy-muted">{{ __('admin.content_rights_jaf_note') }}</p>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.status') }}</span>
                    <select wire:model="contentRightsStatus" class="mt-1 w-full rounded-lg border-border">
                        @foreach ($rightsStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.notes') }}</span>
                    <textarea wire:model="contentRightsNotes" rows="3" class="mt-1 w-full rounded-lg border-border"></textarea>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.supporting_evidence') }}</span>
                    <textarea wire:model="contentRightsEvidence" rows="3" class="mt-1 w-full rounded-lg border-border" placeholder="{{ __('admin.content_rights_evidence_placeholder') }}"></textarea>
                </label>
                <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_content_rights_review') }}</button>
            </form>
        </div>

        @php($japaneseDraft = $entry->translationFor('ja'))
        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.en_ja_editorial_compare') }}</h2>
            <p class="mt-1 text-sm text-navy-muted">{{ __('admin.en_ja_editorial_compare_note') }}</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <article class="rounded-xl border border-border/80 p-3 text-sm">
                    <h3 class="font-semibold text-navy">EN</h3>
                    <p class="mt-2 text-xs text-navy-muted">{{ __('admin.answer_key') }}: {{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</p>
                    <p class="mt-2 whitespace-pre-wrap">{{ $entry->english_question }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-navy-muted">{{ $entry->english_explanation }}</p>
                    @if ($entry->figure_caption)
                        <p class="mt-2 text-xs text-navy-muted">{{ __('admin.figure_caption') }}: {{ $entry->figure_caption }}</p>
                    @endif
                </article>
                <article class="rounded-xl border border-border/80 p-3 text-sm">
                    <h3 class="font-semibold text-navy">JA</h3>
                    @if ($japaneseDraft)
                        <p class="mt-2 text-xs text-navy-muted">{{ __('admin.translation_status', ['status' => $japaneseDraft->status->value]) }}</p>
                        <p class="mt-2 whitespace-pre-wrap">{{ $japaneseDraft->question_text ?: '—' }}</p>
                        <p class="mt-2 whitespace-pre-wrap text-navy-muted">{{ $japaneseDraft->explanation ?: '—' }}</p>
                        @if ($japaneseDraft->figure_caption)
                            <p class="mt-2 text-xs text-navy-muted">{{ __('admin.figure_caption') }}: {{ $japaneseDraft->figure_caption }}</p>
                        @endif
                        @if ($japaneseDraft->nuance_note)
                            <p class="mt-2 text-xs">{{ __('admin.nuance', ['note' => $japaneseDraft->nuance_note]) }}</p>
                        @endif
                        @if (($japaneseDraft->quality_flags ?? []) !== [])
                            <p class="mt-2 text-xs text-amber-800">{{ __('admin.quality_flags', ['flags' => implode(', ', $japaneseDraft->quality_flags)]) }}</p>
                        @endif
                    @else
                        <p class="mt-2 text-navy-muted">{{ __('admin.no_translation_record') }}</p>
                    @endif
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.translations_comparison') }}</h2>
            <p class="mt-1 text-sm text-navy-muted">{{ __('admin.translations_public_note') }}</p>

            @if ($isOriginalPilot ?? false)
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/50 px-3 py-3 text-sm text-amber-950 space-y-1">
                    <p class="font-semibold">{{ __('admin.pilot_translation_panel_title') }}</p>
                    <p>{{ __('admin.pilot_translation_research_status', ['status' => $pilotResearchAssessment ?: '—']) }}</p>
                    <p class="break-all text-xs">{{ __('admin.pilot_fingerprint_label') }}: <code>{{ $pilotSourceFingerprint }}</code></p>
                    <p class="text-xs">{{ __('admin.pilot_translation_not_native') }}</p>
                    @if ($pilotWithheld)
                        <p class="font-semibold text-amber-900" data-pilot-translation-withheld="{{ $pilotWithheld['number'] }}">
                            {{ __('admin.pilot_translation_withheld', ['id' => $pilotWithheld['candidate_id']]) }}
                        </p>
                        <p class="text-xs whitespace-pre-wrap">{{ $pilotWithheld['reason'] }}</p>
                    @endif
                </div>
            @endif

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach ($locales as $locale)
                    @php($translation = $entry->translationFor($locale))
                    @php($stale = ($isOriginalPilot ?? false) && $translation && $locale !== 'ja' && $pilotTranslationDrafts->translationIsStale($entry, $translation))
                    <article class="rounded-xl border border-border/80 p-3 text-sm">
                        <h3 class="font-semibold text-navy uppercase">{{ $locale }}</h3>
                        @if ($translation)
                            <p class="mt-2 text-xs text-navy-muted">{{ __('admin.translation_status', ['status' => $translation->status->value]) }}</p>
                            @if ($isOriginalPilot ?? false)
                                <p class="mt-1 text-xs text-navy-muted">
                                    {{ __('admin.pilot_translation_method') }}:
                                    {{ data_get($translation->japanese_reading_data, 'pilot_translation_meta.drafting_method') ?: '—' }}
                                </p>
                                <p class="mt-1 text-xs text-navy-muted">
                                    {{ __('admin.pilot_translation_native') }}:
                                    {{ data_get($translation->japanese_reading_data, 'pilot_translation_meta.native_reviewed') ? 'true' : 'false' }}
                                </p>
                                @if ($stale)
                                    <p class="mt-1 text-xs font-semibold text-amber-900">{{ __('admin.pilot_translation_stale') }}</p>
                                @endif
                            @endif
                            <p class="mt-2 whitespace-pre-wrap">{{ $translation->question_text ?: '—' }}</p>
                            <p class="mt-2 whitespace-pre-wrap text-navy-muted">{{ $translation->explanation ?: '—' }}</p>
                            @if ($translation->nuance_note)
                                <p class="mt-2 text-xs">{{ __('admin.nuance', ['note' => $translation->nuance_note]) }}</p>
                            @endif
                            @if (($translation->quality_flags ?? []) !== [])
                                <p class="mt-2 text-xs">{{ __('admin.quality_flags', ['flags' => implode(', ', $translation->quality_flags)]) }}</p>
                            @endif
                        @else
                            <p class="mt-2 text-navy-muted">{{ __('admin.no_translation_record') }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.audit_trail') }}</h2>
            <ul class="mt-3 space-y-3 text-sm">
                @forelse ($entry->reviewAudits->sortByDesc('id') as $audit)
                    <li class="border-b border-border/70 pb-3">
                        <p class="font-medium text-navy">{{ $audit->action }} · {{ $audit->user?->email }} · {{ $audit->created_at }}</p>
                        @if ($audit->notes)
                            <p class="mt-1 text-navy-muted">{{ $audit->notes }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-navy-muted">{{ __('admin.no_review_audits') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
