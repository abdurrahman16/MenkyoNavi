<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('admin.questions.original-karimen-pilot') }}" class="text-sm font-medium text-navy-muted hover:text-navy">← {{ __('admin.pilot_accuracy_review_title') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-navy">
                    {{ data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId()) }}
                </h1>
                <p class="mt-1 text-sm text-navy-muted">{{ data_get($entry->source_metadata, 'subject_ja') }} · {{ $entry->legal_topic }}</p>
                @if ($reviewPosition !== null)
                    <p class="mt-1 text-xs font-semibold text-navy-muted">{{ $reviewPosition }} / {{ $reviewTotal }}</p>
                @endif
                <p class="mt-2 text-xs text-amber-900">{{ __('admin.pilot_accuracy_not_publication') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($previousEntryId)
                    <a href="{{ route('admin.questions.original-karimen-pilot.show', $previousEntryId) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.pilot_nav_previous') }}</a>
                @endif
                @if ($nextEntryId)
                    <a href="{{ route('admin.questions.original-karimen-pilot.show', $nextEntryId) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.pilot_nav_next') }}</a>
                @endif
                <a href="{{ route('admin.questions.original-karimen-exam-preview') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_exam_preview') }}</a>
            </div>
        </div>

        @if ($statusMessage !== '')
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

        @if (data_get($entry->source_metadata, 'human_accuracy_approval_stale'))
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                {{ __('admin.pilot_approval_stale_notice') }}
            </div>
        @endif

        @if ($hasSourceProposal && ! $sourceIssueResolved)
            <div class="rounded-xl border-2 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950" data-pilot-source-unresolved="{{ $entry->original_question_number }}">
                <p class="font-semibold">{{ __('admin.pilot_source_issue_unresolved') }}</p>
                <p class="mt-1">{{ $proposal['issue_summary'] ?? '' }}</p>
            </div>
        @endif

        <section class="rounded-2xl border border-border bg-white p-5 space-y-3">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_original') }}</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_col_question') }}</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-navy">{{ $japanese?->question_text ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.proposed_answer') }}</dt>
                    <dd class="mt-1 font-semibold text-navy">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.explanation') }}</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-navy-muted">{{ $japanese?->explanation ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-amber-200/80 bg-amber-50/40 p-5 space-y-3">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_research') }}</h2>
            <p class="text-xs text-amber-900">{{ __('admin.research_assessment_not_human_approval') }}</p>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.research_assessment') }}</dt>
                    <dd class="font-medium text-navy">{{ data_get($entry->source_metadata, 'research_assessment') ?: data_get($auditRow, 'research_assessment', '—') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.answer_key_reference') }}</dt>
                    <dd>{{ $entry->answer_key_reference ?: data_get($entry->source_metadata, 'exact_provision') ?: data_get($auditRow, 'exact_provision', '—') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_evidence_summary') }}</dt>
                    <dd class="whitespace-pre-wrap text-navy-muted">{{ data_get($entry->source_metadata, 'evidence_summary') ?: data_get($auditRow, 'evidence_summary', '—') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.ambiguity_notes') }}</dt>
                    <dd class="whitespace-pre-wrap text-navy-muted">{{ data_get($entry->source_metadata, 'ambiguity') ?: data_get($auditRow, 'ambiguity', '—') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_exceptions') }}</dt>
                    <dd>
                        <ul class="list-disc pl-5 text-navy-muted">
                            @foreach ((array) (data_get($entry->source_metadata, 'exceptions') ?: data_get($auditRow, 'exceptions', [])) as $exception)
                                <li>{{ $exception }}</li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
            </dl>
            @if (filled(data_get($entry->accuracy_evidence_sources, '0.url')))
                <p class="text-sm">
                    <a href="{{ data_get($entry->accuracy_evidence_sources, '0.url') }}" class="font-semibold text-accent underline" target="_blank" rel="noopener noreferrer">
                        {{ __('admin.reference_url') }}
                    </a>
                </p>
            @endif
        </section>

        @if ($hasSourceProposal && is_array($proposal))
            <section class="rounded-2xl border-2 border-amber-400 bg-white p-5 space-y-4" data-pilot-source-revision="{{ $entry->original_question_number }}">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_source_revision_title') }}</h2>
                <p class="text-xs text-amber-900">{{ __('admin.pilot_source_revision_not_approval') }}</p>

                <div>
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_current_stem') }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-navy">{{ $japanese?->question_text }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.proposed_question_revision') }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-navy">{{ $proposal['proposed_question_ja'] }}</p>
                    @unless ($sourceIssueResolved)
                        <p class="mt-1 text-xs text-amber-900">{{ __('admin.proposed_revision_not_applied') }}</p>
                    @endunless
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_proposed_explanation') }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-navy-muted">{{ $proposal['proposed_explanation_ja'] }}</p>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.answer_key_reference') }}</dt>
                        <dd>{{ $proposal['exact_provision'] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_statute_excerpt') }}</dt>
                        <dd class="whitespace-pre-wrap text-navy-muted">{{ $proposal['statute_excerpt'] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_revision_why') }}</dt>
                        <dd class="whitespace-pre-wrap text-navy-muted">{{ $proposal['why_revision_improves_accuracy'] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_remaining_ambiguity') }}</dt>
                        <dd class="whitespace-pre-wrap text-navy-muted">{{ $proposal['remaining_ambiguity'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_translation_eligibility') }}</dt>
                        <dd class="font-medium text-navy">{{ __('admin.pilot_translation_eligibility_'.$translationEligibility) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-navy-muted">{{ __('admin.pilot_gate_published') }}</dt>
                        <dd class="font-medium text-navy">{{ $entry->is_published ? 'true' : 'false' }}</dd>
                    </div>
                </dl>
                <p class="text-sm">
                    <a href="{{ $proposal['source_url'] }}" class="font-semibold text-accent underline" target="_blank" rel="noopener noreferrer">
                        {{ __('admin.reference_url') }}
                    </a>
                </p>
            </section>
        @elseif ((int) $entry->original_question_number === 1)
            <section class="rounded-2xl border-2 border-amber-400 bg-white p-5 space-y-2" data-pilot-special="1">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_special_1_title') }}</h2>
                <p class="text-sm text-navy">{{ __('admin.pilot_special_1_body') }}</p>
                <p class="text-sm whitespace-pre-wrap text-navy-muted">{{ $auditRow['proposed_explanation_ja'] ?? '' }}</p>
            </section>
        @elseif ((int) $entry->original_question_number === 8)
            <section class="rounded-2xl border-2 border-amber-400 bg-white p-5 space-y-3" data-pilot-special="8">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_special_8_title') }}</h2>
                <div>
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_current_stem') }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-navy">{{ $japanese?->question_text }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.proposed_question_revision') }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-navy">{{ data_get($entry->source_metadata, 'proposed_question_ja') ?: ($auditRow['proposed_question_ja'] ?? '—') }}</p>
                    <p class="mt-1 text-xs text-amber-900">{{ __('admin.proposed_revision_not_applied') }}</p>
                </div>
                <p class="text-sm text-navy-muted">{{ $auditRow['proposed_change_reason'] ?? '' }}</p>
            </section>
        @endif

        <section class="rounded-2xl border border-border bg-white p-5 space-y-3" data-pilot-release-gates>
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_gates') }}</h2>
            <ul class="grid gap-2 text-sm sm:grid-cols-2">
                <li>{{ __('admin.pilot_gate_accuracy') }}: <strong>{{ $acceptedCurrent ? __('admin.pilot_status_accepted') : __('admin.pilot_status_unreviewed') }}</strong></li>
                <li>{{ __('admin.pilot_gate_editorial') }}: <strong>{{ $editorialApprovedCurrent ? __('admin.pilot_status_editorial_yes') : __('admin.pilot_status_editorial_no') }}</strong></li>
                <li>{{ __('admin.pilot_gate_rights') }}: <strong>{{ $entry->content_rights_status->label() }}</strong></li>
                <li>{{ __('admin.pilot_gate_answer_key') }}: <strong>{{ $entry->answer_key_status->value }}</strong></li>
                <li>{{ __('admin.pilot_gate_japanese_ready') }}: <strong>{{ $japanese?->status?->value ?? '—' }}</strong></li>
                <li>{{ __('admin.pilot_gate_source_revision') }}: <strong>{{ $sourceIssueResolved ? __('admin.pilot_status_resolved') : __('admin.pilot_status_unresolved') }}</strong></li>
                <li>{{ __('admin.pilot_gate_published') }}: <strong>{{ $entry->is_published ? 'true' : 'false' }}</strong></li>
                <li>{{ __('admin.pilot_gate_promoted') }}: <strong>{{ $entry->promoted_question_id ? (string) $entry->promoted_question_id : 'null' }}</strong></li>
            </ul>
            <p class="text-xs text-navy-muted">{{ __('admin.pilot_fingerprint_label') }}: <code>{{ $fingerprint }}</code></p>
            @if ($publicationBlockers !== [])
                <p class="text-xs text-amber-900">{{ __('admin.pilot_publication_blockers') }}: {{ implode(', ', $publicationBlockers) }}</p>
            @endif
        </section>

        <section class="rounded-2xl border border-teal/30 bg-teal/5 p-5 space-y-6" data-pilot-release-actions>
            <div>
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_release_actions_title') }}</h2>
                <p class="mt-1 text-xs text-navy-muted">{{ __('admin.pilot_release_actions_help') }}</p>
            </div>

            <form wire:submit="submitEditorialDecision" class="space-y-3 rounded-xl border border-border bg-white p-4">
                <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_gate_editorial') }}</h3>
                <input type="hidden" wire:model="expectedContentFingerprint">
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="editorialApproved" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_editorial_approve_label') }}</span>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_editorial_notes_label') }}</span>
                    <textarea wire:model="editorialNotes" rows="3" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
                </label>
                @if ($editorialApproved)
                    <label class="flex min-h-11 items-start gap-2 text-sm">
                        <input type="checkbox" wire:model="confirmEditorialApproval" class="mt-1 rounded border-border text-accent focus:ring-accent">
                        <span>{{ __('admin.pilot_editorial_confirm') }}</span>
                    </label>
                @endif
                <button type="submit" wire:confirm="{{ __('admin.pilot_editorial_submit_confirm') }}" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">{{ __('admin.pilot_editorial_submit') }}</button>
            </form>

            <form wire:submit="submitAnswerKeyDecision" class="space-y-3 rounded-xl border border-border bg-white p-4">
                <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_gate_answer_key') }}</h3>
                <input type="hidden" wire:model="expectedContentFingerprint">
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="answerKeyVerified" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_answer_key_verify_label') }}</span>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.answer_key_reference') }}</span>
                    <input type="text" wire:model="answerKeyReference" class="mt-1 w-full rounded-lg border-border">
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_answer_key_notes_label') }}</span>
                    <textarea wire:model="answerKeyNotes" rows="3" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
                </label>
                <button type="submit" wire:confirm="{{ __('admin.pilot_answer_key_submit_confirm') }}" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">{{ __('admin.pilot_answer_key_submit') }}</button>
            </form>

            <form wire:submit="submitRightsDecision" class="space-y-3 rounded-xl border border-border bg-white p-4">
                <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_gate_rights') }}</h3>
                <input type="hidden" wire:model="expectedContentFingerprint">
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="rightsCleared" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_rights_clear_label') }}</span>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_rights_evidence_label') }}</span>
                    <textarea wire:model="rightsEvidence" rows="2" class="mt-1 w-full rounded-lg border-border"></textarea>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_rights_notes_label') }}</span>
                    <textarea wire:model="rightsNotes" rows="3" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
                </label>
                <button type="submit" wire:confirm="{{ __('admin.pilot_rights_submit_confirm') }}" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">{{ __('admin.pilot_rights_submit') }}</button>
            </form>

            <form wire:submit="markJapaneseReady" class="space-y-3 rounded-xl border border-border bg-white p-4">
                <h3 class="text-sm font-semibold text-navy">{{ __('admin.pilot_gate_japanese_ready') }}</h3>
                <input type="hidden" wire:model="expectedContentFingerprint">
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_japanese_ready_notes_label') }}</span>
                    <textarea wire:model="japaneseReadyNotes" rows="3" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
                </label>
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="confirmJapaneseReady" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_japanese_ready_confirm') }}</span>
                </label>
                <button type="submit" wire:confirm="{{ __('admin.pilot_japanese_ready_submit_confirm') }}" class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">{{ __('admin.pilot_japanese_ready_submit') }}</button>
            </form>

            @unless ($entry->is_published)
                <div class="rounded-xl border border-amber-300 bg-amber-50/40 p-4 space-y-2">
                    <h3 class="text-sm font-semibold text-navy">{{ __('admin.publish_to_live_pool') }}</h3>
                    <p class="text-xs text-amber-950">{{ __('admin.pilot_publish_help') }}</p>
                    <button
                        type="button"
                        wire:click="publishEligibleCandidate"
                        wire:confirm="{{ __('admin.publish_to_live_pool_confirm') }}"
                        class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white"
                    >
                        {{ __('admin.publish_to_live_pool') }}
                    </button>
                </div>
            @endunless
        </section>

        @if ($hasSourceProposal)
            <section class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_revision_history') }}</h2>
                @if ($revisionHistory->isEmpty())
                    <p class="text-sm font-semibold text-amber-800">{{ __('admin.pilot_revision_status_none') }}</p>
                @else
                    <ul class="space-y-3 text-sm">
                        @foreach ($revisionHistory as $event)
                            <li class="rounded-xl border border-border/70 px-3 py-2">
                                <p class="font-semibold text-navy">
                                    {{ \App\Enums\SourceRevisionDecision::tryFrom((string) data_get($event->after, 'source_revision_decision', ''))?->labelJa()
                                        ?? data_get($event->after, 'source_revision_decision', '—') }}
                                </p>
                                <p class="text-xs text-navy-muted">
                                    {{ $event->user?->email ?: ('#'.$event->user_id) }}
                                    · {{ $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                </p>
                                <p class="mt-1 whitespace-pre-wrap text-navy-muted">{{ $event->notes }}</p>
                                <p class="mt-1 break-all text-[11px] text-navy-muted">
                                    {{ __('admin.pilot_fingerprint_label') }}:
                                    {{ data_get($event->after, 'content_fingerprint_before') }}
                                    →
                                    {{ data_get($event->after, 'content_fingerprint_after') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <form wire:submit="submitRevisionDecision" class="rounded-2xl border border-amber-300 bg-amber-50/30 p-5 space-y-4" data-pilot-revision-form>
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_revision_decision') }}</h2>
                <p class="text-xs text-navy-muted">{{ __('admin.pilot_revision_decision_hint') }}</p>

                <input type="hidden" wire:model="expectedContentFingerprint">

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-navy">{{ __('admin.pilot_revision_decision_legend') }}</legend>
                    @foreach ($revisionDecisions as $option)
                        <label class="flex min-h-11 items-start gap-2 text-sm">
                            <input
                                type="radio"
                                wire:model.live="revisionDecision"
                                value="{{ $option->value }}"
                                class="mt-1 border-border text-accent focus:ring-accent"
                            >
                            <span>
                                <span class="font-semibold text-navy">{{ $option->labelJa() }}</span>
                                <span class="block text-xs text-navy-muted">{{ $option->label() }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.pilot_revision_notes_label') }}</span>
                    <textarea wire:model="revisionNotes" rows="4" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
                </label>

                @if ($revisionDecision === \App\Enums\SourceRevisionDecision::ApplyProposedRevision->value)
                    <label class="flex min-h-11 items-start gap-2 text-sm">
                        <input type="checkbox" wire:model="confirmRevisionApply" class="mt-1 rounded border-border text-accent focus:ring-accent">
                        <span>{{ __('admin.pilot_confirm_revision_apply') }}</span>
                    </label>
                @endif

                <button
                    type="submit"
                    wire:confirm="{{ __('admin.pilot_confirm_revision_submit') }}"
                    wire:loading.attr="disabled"
                    class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {{ __('admin.pilot_submit_revision_decision') }}
                </button>
            </form>
        @endif

        <section class="rounded-2xl border border-border bg-white p-5 space-y-3">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_history') }}</h2>
            @if ($history->isEmpty())
                <p class="text-sm font-semibold text-amber-800">{{ __('admin.pilot_status_unreviewed') }}</p>
            @else
                <ul class="space-y-3 text-sm">
                    @foreach ($history as $event)
                        <li class="rounded-xl border border-border/70 px-3 py-2">
                            <p class="font-semibold text-navy">
                                {{ \App\Enums\HumanAccuracyReviewDecision::tryFrom((string) data_get($event->after, 'human_accuracy_decision', ''))?->labelJa()
                                    ?? data_get($event->after, 'human_accuracy_decision', '—') }}
                            </p>
                            <p class="text-xs text-navy-muted">
                                {{ $event->user?->email ?: ('#'.$event->user_id) }}
                                · {{ $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                            </p>
                            <p class="mt-1 whitespace-pre-wrap text-navy-muted">{{ $event->notes }}</p>
                            <p class="mt-1 break-all text-[11px] text-navy-muted">
                                {{ __('admin.pilot_fingerprint_label') }}:
                                {{ data_get($event->after, 'reviewed_content_fingerprint') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <form wire:submit="submitDecision" class="rounded-2xl border border-navy/20 bg-white p-5 space-y-4">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.pilot_section_decision') }}</h2>
            <p class="text-xs text-navy-muted">{{ __('admin.pilot_decision_hint') }}</p>

            <input type="hidden" wire:model="expectedContentFingerprint">

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-navy">{{ __('admin.pilot_decision_legend') }}</legend>
                @foreach ($decisions as $option)
                    <label class="flex min-h-11 items-start gap-2 text-sm">
                        <input
                            type="radio"
                            wire:model.live="decision"
                            value="{{ $option->value }}"
                            class="mt-1 border-border text-accent focus:ring-accent"
                        >
                        <span>
                            <span class="font-semibold text-navy">{{ $option->labelJa() }}</span>
                            <span class="block text-xs text-navy-muted">{{ $option->label() }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.pilot_notes_label') }}</span>
                <textarea wire:model="notes" rows="5" class="mt-1 w-full rounded-lg border-border" required minlength="12"></textarea>
            </label>

            @if ($decision === \App\Enums\HumanAccuracyReviewDecision::Accepted->value)
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="confirmAcceptance" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_confirm_acceptance') }}</span>
                </label>
            @endif

            @if ((int) $entry->original_question_number === 1)
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="acknowledgesScopeNote" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_ack_scope_1') }}</span>
                </label>
            @endif

            @if ((int) $entry->original_question_number === 8)
                <label class="flex min-h-11 items-start gap-2 text-sm">
                    <input type="checkbox" wire:model="acknowledgesProposedStem" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.pilot_ack_stem_8') }}</span>
                </label>
            @endif

            <button
                type="submit"
                wire:confirm="{{ __('admin.pilot_confirm_submit') }}"
                wire:loading.attr="disabled"
                class="inline-flex min-h-11 items-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
                {{ __('admin.pilot_submit_decision') }}
            </button>
        </form>
    </div>
</div>
