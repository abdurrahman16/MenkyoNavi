@php
    $stagedTotal = max(1, (int) ($report['staged_questions_total'] ?? 0));
    $firstSource = (int) (($byFamily['karimen'] ?? 0) + ($byFamily['honmen'] ?? 0));
    $secondSource = (int) ($byFamily['unspecified'] ?? $classificationStats['unclassified_second_source'] ?? 0);
@endphp

<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('admin.internal_editorial') }}</p>
                <h1 class="text-2xl font-bold text-navy">{{ __('admin.dashboard_title') }}</h1>
                <p class="mt-1 text-sm text-navy-muted">{{ __('admin.dashboard_subtitle') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button type="button" wire:click="generateProposals" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">
                    {{ __('admin.generate_proposals') }}
                </button>
                <a href="{{ route('admin.questions.bulk-classification') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_bulk_classification') }}</a>
                <a href="{{ route('admin.questions.index', ['sourceGroup' => 'karimen_test_1']) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_karimen_test_1') }}</a>
                <a href="{{ route('admin.questions.original-karimen-pilot') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_pilot') }}</a>
                <a href="{{ route('admin.questions.original-karimen-exam-preview') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border-2 border-amber-500 bg-amber-50 px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_exam_preview') }}</a>
                <a href="{{ route('admin.questions.index', ['sourceKey' => 'menkyo_original_karimen_expansion_001']) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_original_karimen_expansion') }}</a>
                <button type="button" wire:click="exportKarimenTest1Audit" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">
                    {{ __('admin.export_karimen_test_1_audit') }}
                </button>
                <button type="button" wire:click="exportKarimenTest1PriorityReview" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">
                    {{ __('admin.export_karimen_test_1_priority_review') }}
                </button>
                <a href="{{ route('admin.questions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.browse_questions') }}</a>
                <a href="{{ route('admin.questions.duplicates') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.similar_pairs') }} ({{ $similarPairsPending }})</a>
            </div>
        </div>

        @if ($statusMessage !== '')
            <div class="rounded-xl border border-teal/30 bg-teal/10 px-4 py-3 text-sm text-navy" role="status">{{ $statusMessage }}</div>
        @endif

        @if ($karimenPriorityReview['priority_entries']->isNotEmpty() || $karimenPriorityReview['figure_dependent_entries']->isNotEmpty())
            <section class="rounded-2xl border border-amber-200/80 bg-amber-50/30 p-5 space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-navy">{{ __('admin.priority_review_section_title') }}</h2>
                        <p class="mt-1 text-sm text-navy-muted">{{ __('admin.priority_review_section_note') }}</p>
                    </div>
                </div>
                @if ($karimenPriorityReview['batch_rights_status'])
                    <p class="text-sm">
                        <span class="font-medium text-navy">{{ __('admin.batch_content_rights_status') }}:</span>
                        {{ app()->getLocale() === 'ja' ? $karimenPriorityReview['batch_rights_status']->labelJa() : $karimenPriorityReview['batch_rights_status']->label() }}
                        @if ($karimenPriorityReview['batch'])
                            <span class="text-navy-muted">· {{ $karimenPriorityReview['batch']->source_filename }}</span>
                        @endif
                    </p>
                @endif
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold text-navy">{{ __('admin.priority_questions_heading') }}</h3>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($karimenPriorityReview['priority_entries'] as $priorityEntry)
                                <li class="rounded-lg border border-border/70 bg-white px-3 py-2">
                                    <a href="{{ route('admin.questions.show', $priorityEntry) }}" class="font-semibold text-navy underline">
                                        {{ __('admin.priority_question_link', ['number' => $priorityEntry->original_question_number, 'id' => $priorityEntry->id]) }}
                                    </a>
                                    <p class="mt-1 line-clamp-2 text-xs text-navy-muted">{{ $priorityEntry->english_question }}</p>
                                </li>
                            @empty
                                <li class="text-navy-muted">{{ __('admin.no_priority_questions') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-navy">{{ __('admin.figure_dependent_questions_heading', ['count' => $karimenPriorityReview['figure_dependent_entries']->count()]) }}</h3>
                        <ul class="mt-2 max-h-64 space-y-1 overflow-y-auto text-sm">
                            @foreach ($karimenPriorityReview['figure_dependent_entries'] as $figureEntry)
                                <li>
                                    <a href="{{ route('admin.questions.show', $figureEntry) }}" class="text-navy underline">
                                        Q{{ $figureEntry->original_question_number }} (#{{ $figureEntry->id }})
                                    </a>
                                    @if ($figureEntry->sign_key)
                                        <span class="text-xs text-navy-muted">· {{ $figureEntry->sign_key }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-border bg-white p-4">
                <p class="text-xs uppercase text-navy-muted">{{ __('admin.staged_entries') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $report['staged_questions_total'] }}</p>
            </div>
            <div class="rounded-2xl border border-border bg-white p-4">
                <p class="text-xs uppercase text-navy-muted">{{ __('admin.first_source_entries') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $firstSource }}</p>
                <p class="mt-1 text-xs text-navy-muted">Karimen {{ $byFamily['karimen'] ?? 0 }} · Honmen {{ $byFamily['honmen'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-border bg-white p-4">
                <p class="text-xs uppercase text-navy-muted">{{ __('admin.second_source_entries') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $secondSource }}</p>
            </div>
            <div class="rounded-2xl border border-border bg-white p-4">
                <p class="text-xs uppercase text-navy-muted">{{ __('admin.publication_ready') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $report['publication_ready_questions'] }}</p>
            </div>
        </div>

        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.classification_progress') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.approved') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['approved'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.pending') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['pending'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.proposed') }} Karimen</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['proposed_karimen'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.proposed') }} Honmen</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['proposed_honmen'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.unclassified_exam') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['proposed_unclassified'] ?: $classificationStats['unclassified_second_source'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.study_topics_assigned') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['study_topics_assigned'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.study_topics_approved_count') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['study_topics_approved'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.pending_editorial_reviews') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['pending_editorial_reviews'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.verified_answer_keys') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['verified_answer_keys'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.disputed_answer_keys') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['disputed_answer_keys'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.content_rights_approvals') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $classificationStats['content_rights_approved'] }}</p>
                </div>
                <div class="rounded-xl border border-border bg-surface p-3">
                    <p class="text-xs text-navy-muted">{{ __('admin.draft_translation_ready') }}</p>
                    <p class="mt-1 text-2xl font-bold text-navy">{{ $draftPromotionReady }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-border bg-white p-5">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.by_source') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($batches as $batch)
                        <li class="flex justify-between gap-3 border-b border-border/70 py-2">
                            <span class="min-w-0 break-words font-medium text-navy">{{ $batch->source_key }}</span>
                            <span class="shrink-0 text-navy-muted">{{ $batch->entry_count }}</span>
                        </li>
                    @empty
                        <li class="text-navy-muted">{{ __('admin.no_staged_batches') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-2xl border border-border bg-white p-5">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.source_exam_family') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($byFamily as $family => $count)
                        <li class="flex justify-between gap-3 border-b border-border/70 py-2">
                            <span class="font-medium text-navy">{{ $family }}</span>
                            <span class="text-navy-muted">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl border border-border bg-white p-5">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.editorial_categories') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($byEditorialCategory as $category => $count)
                        <li class="flex justify-between gap-3 border-b border-border/70 py-2">
                            <span class="min-w-0 break-words font-medium text-navy">{{ $category }}</span>
                            <span class="shrink-0 text-navy-muted">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl border border-border bg-white p-5">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.study_topics') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($byStudyTopic as $topic => $count)
                        <li class="flex justify-between gap-3 border-b border-border/70 py-2">
                            <span class="min-w-0 break-words font-medium text-navy">{{ $topic }}</span>
                            <span class="shrink-0 text-navy-muted">{{ $count }}</span>
                        </li>
                    @empty
                        <li class="text-navy-muted">{{ __('admin.no_study_topics_yet') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-2xl border border-border bg-white p-5 lg:col-span-2">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.answer_key_rights') }}</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="font-semibold text-navy">{{ __('admin.answer_key') }}</p>
                        @foreach ($report['answer_key_verification'] as $status => $count)
                            <p class="mt-1 flex justify-between gap-2"><span class="break-words">{{ $status }}</span><span>{{ $count }}</span></p>
                        @endforeach
                    </div>
                    <div>
                        <p class="font-semibold text-navy">{{ __('admin.content_rights') }}</p>
                        @foreach ($report['content_rights_approval'] as $status => $count)
                            <p class="mt-1 flex justify-between gap-2"><span class="break-words">{{ $status }}</span><span>{{ $count }}</span></p>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-border bg-white p-5">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.translation_progress') }}</h2>
            <p class="mt-1 text-sm text-navy-muted">{{ __('admin.translation_progress_note') }}</p>

            <div class="mt-4 space-y-3 lg:hidden">
                @foreach ($report['translations_with_text_by_locale'] as $locale => $count)
                    @php
                        $draft = (int) ($report['draft_translations_by_locale'][$locale] ?? 0);
                        $reviewed = (int) ($report['reviewed_translations_by_locale'][$locale] ?? 0);
                        $coverage = (int) round(($count / $stagedTotal) * 100);
                    @endphp
                    <article class="rounded-xl border border-border bg-surface p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-navy">{{ $locale }}</p>
                            <p class="text-sm font-semibold text-accent">{{ $coverage }}%</p>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $coverage) }}%"></div>
                        </div>
                        <dl class="mt-3 grid grid-cols-3 gap-2 text-xs text-navy-muted">
                            <div>
                                <dt>{{ __('admin.with_text') }}</dt>
                                <dd class="text-sm font-semibold text-navy">{{ $count }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('admin.draft') }}</dt>
                                <dd class="text-sm font-semibold text-navy">{{ $draft }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('admin.reviewed') }}</dt>
                                <dd class="text-sm font-semibold text-navy">{{ $reviewed }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>

            <div class="mt-4 hidden overflow-x-auto lg:block">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-border text-navy-muted">
                        <tr>
                            <th class="py-2 pr-4 font-medium">{{ __('admin.locale') }}</th>
                            <th class="py-2 pr-4 font-medium">{{ __('admin.with_text') }}</th>
                            <th class="py-2 pr-4 font-medium">{{ __('admin.draft') }}</th>
                            <th class="py-2 pr-4 font-medium">{{ __('admin.reviewed') }}</th>
                            <th class="py-2 font-medium">{{ __('admin.coverage') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['translations_with_text_by_locale'] as $locale => $count)
                            @php
                                $draft = (int) ($report['draft_translations_by_locale'][$locale] ?? 0);
                                $reviewed = (int) ($report['reviewed_translations_by_locale'][$locale] ?? 0);
                                $coverage = (int) round(($count / $stagedTotal) * 100);
                            @endphp
                            <tr class="border-b border-border/60">
                                <td class="py-3 pr-4 font-semibold text-navy">{{ $locale }}</td>
                                <td class="py-3 pr-4">{{ $count }}</td>
                                <td class="py-3 pr-4">{{ $draft }}</td>
                                <td class="py-3 pr-4">{{ $reviewed }}</td>
                                <td class="py-3 min-w-[12rem]">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface">
                                            <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $coverage) }}%"></div>
                                        </div>
                                        <span class="w-10 text-right font-semibold text-navy">{{ $coverage }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
