<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('admin.internal_editorial') }}</p>
                <h1 class="text-2xl font-bold text-navy">{{ __('admin.pilot_accuracy_review_title') }}</h1>
                <p class="mt-1 text-sm text-navy-muted">{{ __('admin.pilot_accuracy_review_subtitle', ['key' => $collectionKey.' + '.$expansionCollectionKey, 'total' => $totalOriginal]) }}</p>
                <p class="mt-1 text-xs text-navy-muted">{{ __('admin.original_review_collection_counts', ['pilot' => $totalPilot, 'expansion' => $totalExpansion]) }}</p>
                <p class="mt-2 text-xs text-amber-900">{{ __('admin.pilot_accuracy_not_publication') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="{{ route('admin.questions.original-karimen-exam-preview') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_exam_preview') }}</a>
                <a href="{{ route('admin.questions.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.dashboard_title') }}</a>
                <a href="{{ route('admin.questions.index', ['sourceKey' => $collectionKey]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.browse_questions') }}</a>
                <a href="{{ route('admin.questions.index', ['sourceKey' => $expansionCollectionKey]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_expansion') }}</a>
            </div>
        </div>

        <section class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4 text-sm text-amber-950">
            <h2 class="font-semibold text-navy">{{ __('admin.pilot_translation_overview_title') }}</h2>
            <p class="mt-1 text-xs">{{ __('admin.pilot_translation_overview_note', ['eligible' => count($eligibleTranslationNumbers), 'withheld' => count($withheldTranslations)]) }}</p>
            <ul class="mt-2 list-disc pl-5 text-xs space-y-1">
                @foreach ($withheldTranslations as $withheld)
                    <li>
                        <span class="font-semibold">{{ $withheld['candidate_id'] }}</span>
                        — {{ $withheld['reason'] }}
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('admin.original_collection_filter_label') }}">
            @foreach ([
                'all' => __('admin.original_collection_filter_all'),
                'pilot' => __('admin.original_collection_filter_pilot'),
                'expansion' => __('admin.original_collection_filter_expansion'),
            ] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('collection', '{{ $value }}')"
                    class="inline-flex min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold {{ $collection === $value ? 'bg-teal text-white' : 'border border-border bg-white text-navy' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('admin.pilot_filter_label') }}">
            @foreach ([
                'pending' => __('admin.pilot_filter_pending'),
                'needs_clarification' => __('admin.pilot_filter_needs_clarification'),
                'accepted' => __('admin.pilot_filter_accepted'),
                'revision_requested' => __('admin.pilot_filter_revision_requested'),
                'rejected_deferred' => __('admin.pilot_filter_rejected_deferred'),
                'all' => __('admin.pilot_filter_all'),
            ] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $value }}')"
                    class="inline-flex min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold {{ $filter === $value ? 'bg-navy text-white' : 'border border-border bg-white text-navy' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-2xl border border-border bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-border bg-cream/40 text-xs uppercase tracking-wide text-navy-muted">
                    <tr>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_id') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_collection') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_topic') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_question') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_answer') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.research_assessment') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_human_accuracy') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_editorial') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_publication') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_reviewed_at') }}</th>
                        <th class="px-3 py-3 font-semibold">{{ __('admin.pilot_col_action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        @php
                            $ja = $entry->translationFor('ja');
                            $decision = \App\Enums\HumanAccuracyReviewDecision::tryFrom((string) data_get($entry->source_metadata, 'human_accuracy_decision', ''));
                            $accepted = $reviews->humanAccuracyAcceptedForCurrentVersion($entry);
                            $pendingIssue = data_get($entry->source_metadata, 'research_assessment') === 'supported_with_clarification'
                                || in_array('needs_editorial_clarification', $entry->review_flags ?? [], true);
                        @endphp
                        <tr class="border-b border-border/70 align-top" wire:key="original-{{ $entry->id }}">
                            <td class="px-3 py-3 font-mono text-xs text-navy">
                                {{ data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId()) }}
                                @if ($pendingIssue)
                                    <span class="mt-1 block rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-900">{{ __('admin.pilot_pending_issue') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-navy-muted">{{ $entry->source_group }}</td>
                            <td class="px-3 py-3 text-navy">{{ data_get($entry->source_metadata, 'subject_ja') ?: ($entry->legal_topic ?: '—') }}</td>
                            <td class="px-3 py-3 text-navy">
                                <p class="line-clamp-3 max-w-md">{{ $ja?->question_text ?: '—' }}</p>
                            </td>
                            <td class="px-3 py-3 font-semibold text-navy">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</td>
                            <td class="px-3 py-3 text-xs text-navy-muted">{{ data_get($entry->source_metadata, 'research_assessment') ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs">
                                @if ($accepted)
                                    <span class="font-semibold text-emerald-800">{{ __('admin.pilot_status_accepted') }}</span>
                                @elseif ($decision)
                                    <span class="font-semibold text-navy">{{ $decision->labelJa() }}</span>
                                @else
                                    <span class="font-semibold text-amber-800">{{ __('admin.pilot_status_unreviewed') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-navy-muted">
                                {{ data_get($entry->source_metadata, 'human_editorial_approval') ? __('admin.pilot_status_editorial_yes') : __('admin.pilot_status_editorial_no') }}
                            </td>
                            <td class="px-3 py-3 text-xs text-navy-muted">
                                {{ $entry->is_published ? __('admin.pilot_status_published') : __('admin.pilot_status_unpublished') }}
                            </td>
                            <td class="px-3 py-3 text-xs text-navy-muted">
                                {{ $entry->accuracy_reviewed_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?: '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <a href="{{ route('admin.questions.original-karimen-pilot.show', $entry) }}" class="inline-flex min-h-11 items-center font-semibold text-accent underline">
                                    {{ __('admin.pilot_open_review') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-8 text-center text-navy-muted">{{ __('admin.pilot_no_matches') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
