<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.questions.dashboard') }}" class="text-sm font-medium text-navy-muted hover:text-navy">← {{ __('admin.dashboard') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-navy">{{ __('admin.staged_questions') }}</h1>
                <p class="mt-1 text-sm text-navy-muted">{{ __('admin.index_subtitle') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.questions.bulk-classification') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.open_bulk_classification') }}</a>
                <p class="text-sm text-navy-muted sm:self-center">{{ __('admin.matching_entries', ['count' => $entries->total()]) }}</p>
            </div>
        </div>

        @if ($statusMessage !== '')
            <div class="rounded-xl border border-teal/30 bg-teal/10 px-4 py-3 text-sm text-navy" role="status">{{ $statusMessage }}</div>
        @endif

        <form class="grid gap-3 rounded-2xl border border-border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4" wire:submit.prevent>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.search') }}</span>
                <input type="search" wire:model.live.debounce.400ms="search" class="mt-1 w-full rounded-lg border-border" placeholder="{{ __('admin.index_filter_search_placeholder') }}">
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.source') }}</span>
                <select wire:model.live="sourceKey" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all_sources') }}</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->source_key }}">{{ $batch->source_key }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.group') }}</span>
                <select wire:model.live="sourceGroup" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all_groups') }}</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group }}">{{ $group }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.source_exam_family') }}</span>
                <select wire:model.live="examFamily" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($families as $family)
                        <option value="{{ $family }}">{{ $family }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.workflow') }}</span>
                <select wire:model.live="workflow" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($workflows as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.editorial_category') }}</span>
                <select wire:model.live="editorialCategory" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}">{{ $category->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.proposed_exam_category') }}</span>
                <select wire:model.live="proposedCategory" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}">{{ $category->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.study_topics') }}</span>
                <select wire:model.live="studyTopic" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach ($studyTopics as $topic)
                        <option value="{{ $topic->value }}">{{ $topic->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="font-medium text-navy">{{ __('admin.classification_state') }}</span>
                <select wire:model.live="classificationState" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="approved">{{ __('admin.approved') }}</option>
                    <option value="pending">{{ __('admin.pending') }}</option>
                    <option value="unclassified_exam">{{ __('admin.unclassified_exam') }}</option>
                </select>
            </label>
            <label class="block text-sm sm:col-span-2">
                <span class="font-medium text-navy">{{ __('admin.missing_translation') }}</span>
                <select wire:model.live="missingTranslation" class="mt-1 w-full rounded-lg border-border">
                    <option value="">{{ __('admin.any') }}</option>
                    @foreach ($locales as $locale)
                        <option value="{{ $locale }}">{{ __('admin.missing_locale', ['locale' => $locale]) }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <section class="rounded-2xl border border-border bg-white p-4 space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <h2 class="text-sm font-semibold text-navy">{{ __('admin.bulk_actions') }} · {{ __('admin.selected_count', ['count' => count($selectedIds)]) }}</h2>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="selectPageResults" class="rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_select_page') }}</button>
                    <button type="button" wire:click="clearSelection" class="rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_clear_selection') }}</button>
                </div>
            </div>
            <div class="grid gap-3 lg:grid-cols-4">
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.study_topics') }}</span>
                    <select wire:model="bulkTopic" class="mt-1 w-full rounded-lg border-border">
                        <option value="">{{ __('admin.all') }}</option>
                        @foreach ($studyTopics as $topic)
                            <option value="{{ $topic->value }}">{{ $topic->label() }}</option>
                        @endforeach
                    </select>
                    @error('bulkTopic') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </label>
                <label class="block text-sm lg:col-span-2">
                    <span class="font-medium text-navy">{{ __('admin.editorial_notes') }}</span>
                    <input type="text" wire:model="bulkNotes" class="mt-1 w-full rounded-lg border-border" maxlength="2000">
                    @error('bulkNotes') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </label>
                <label class="flex min-h-11 items-end gap-2 text-sm text-navy pb-2">
                    <input type="checkbox" wire:model="confirmBulkAction" class="rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.bulk_confirm_label') }}</span>
                </label>
            </div>
            @error('confirmBulkAction') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @error('selectedIds') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button type="button" wire:click="bulkAssignTopics" wire:confirm="{{ __('admin.bulk_confirm_dialog') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.bulk_assign_topics') }}</button>
                <button type="button" wire:click="bulkMarkReview" wire:confirm="{{ __('admin.bulk_confirm_dialog') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_mark_review') }}</button>
                <button type="button" wire:click="bulkAppendNotes" wire:confirm="{{ __('admin.bulk_confirm_dialog') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_append_notes') }}</button>
            </div>
            <div class="grid gap-3 border-t border-border pt-3 sm:grid-cols-3">
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.export_locale') }}</span>
                    <select wire:model="exportLocale" class="mt-1 w-full rounded-lg border-border">
                        @foreach ($locales as $locale)
                            <option value="{{ $locale }}">{{ $locale }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-navy">{{ __('admin.export_limit') }}</span>
                    <input type="number" wire:model="exportLimit" min="1" max="100" class="mt-1 w-full rounded-lg border-border">
                </label>
                <div class="flex items-end">
                    <button type="button" wire:click="exportSelectedForTranslation" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white">{{ __('admin.bulk_export') }}</button>
                </div>
            </div>
            <p class="text-xs text-navy-muted">{{ __('admin.bulk_does_not_approve_rights') }}</p>
        </section>

        <div class="space-y-3 lg:hidden">
            @foreach ($entries as $entry)
                <article class="rounded-2xl border border-border bg-white p-4" wire:key="mobile-{{ $entry->id }}">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            class="mt-1 rounded border-border text-accent focus:ring-accent"
                            wire:click="toggleSelect({{ $entry->id }})"
                            @checked(in_array($entry->id, $selectedIds, true))
                        >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-navy-muted">{{ $entry->batch?->source_key }} · {{ $entry->source_group }} #{{ $entry->original_question_number }}</p>
                            <p class="mt-2 text-sm font-semibold text-navy break-words">{{ \Illuminate\Support\Str::limit($entry->english_question, 160) }}</p>
                            <p class="mt-2 text-xs text-navy-muted">
                                {{ __('admin.answer') }}: {{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}
                                · {{ __('admin.proposed') }}: {{ $entry->proposed_exam_category ?? '—' }}
                                · {{ implode(', ', $entry->study_topics ?? []) ?: '—' }}
                            </p>
                            <a href="{{ route('admin.questions.show', $entry) }}" class="mt-3 inline-block text-sm font-semibold text-navy underline">{{ __('admin.open_review') }}</a>
                        </div>
                    </label>
                </article>
            @endforeach
        </div>

        <div class="hidden overflow-x-auto rounded-2xl border border-border bg-white lg:block">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-border bg-surface text-navy-muted">
                    <tr>
                        <th class="px-3 py-3 font-medium"></th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.source') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.group_number') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.question') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.answer') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.family') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.study_topics') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.workflow') }}</th>
                        <th class="px-3 py-3 font-medium">{{ __('admin.translations') }}</th>
                        <th class="px-3 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr class="border-b border-border/70 align-top" wire:key="entry-{{ $entry->id }}">
                            <td class="px-3 py-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-border text-accent focus:ring-accent"
                                    wire:click="toggleSelect({{ $entry->id }})"
                                    @checked(in_array($entry->id, $selectedIds, true))
                                >
                            </td>
                            <td class="px-3 py-3 text-xs">{{ $entry->batch?->source_key }}</td>
                            <td class="px-3 py-3 text-xs">{{ $entry->source_group }}<br>#{{ $entry->original_question_number }}</td>
                            <td class="px-3 py-3 max-w-md break-words">{{ \Illuminate\Support\Str::limit($entry->english_question, 140) }}</td>
                            <td class="px-3 py-3">{{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</td>
                            <td class="px-3 py-3 text-xs">
                                {{ __('admin.exam_family_source', ['value' => $entry->exam_family]) }}<br>
                                {{ __('admin.proposed') }}: {{ $entry->proposed_exam_category ?? '—' }}<br>
                                {{ __('admin.exam_family_editorial', ['value' => implode(', ', $entry->editorial_categories ?? ['—'])]) }}
                            </td>
                            <td class="px-3 py-3 text-xs break-words">{{ implode(', ', $entry->study_topics ?? []) ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs">
                                {{ $entry->editorial_workflow_status->label() }}<br>
                                {{ __('admin.answer_key_short', ['value' => $entry->answer_key_status->value]) }}
                            </td>
                            <td class="px-3 py-3 text-xs">
                                {{ $entry->translations->filter(fn ($t) => filled($t->question_text))->pluck('locale')->implode(', ') ?: __('admin.none') }}
                            </td>
                            <td class="px-3 py-3">
                                <a href="{{ route('admin.questions.show', $entry) }}" class="font-semibold text-navy underline">{{ __('admin.review') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
            <p class="text-navy-muted">
                {{ __('admin.page_of', ['current' => $entries->currentPage(), 'last' => max(1, $entries->lastPage())]) }}
            </p>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-border bg-white px-3 py-2 font-semibold text-navy disabled:opacity-40"
                    wire:click="previousPage"
                    @disabled($entries->onFirstPage())
                >{{ __('admin.previous') }}</button>
                <button
                    type="button"
                    class="rounded-lg border border-border bg-white px-3 py-2 font-semibold text-navy disabled:opacity-40"
                    wire:click="nextPage"
                    @disabled(! $entries->hasMorePages())
                >{{ __('admin.next') }}</button>
            </div>
        </div>
    </div>
</div>
