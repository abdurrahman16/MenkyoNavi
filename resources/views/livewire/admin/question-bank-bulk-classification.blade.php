<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.questions.dashboard') }}" class="text-sm font-medium text-navy-muted hover:text-navy">← {{ __('admin.back_to_dashboard') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-navy">{{ __('admin.bulk_classification_title') }}</h1>
                <p class="mt-1 text-sm text-navy-muted">{{ __('admin.bulk_classification_subtitle') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button" wire:click="generateProposals" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">
                    {{ __('admin.generate_proposals') }}
                </button>
                <a href="{{ route('admin.questions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">
                    {{ __('admin.browse_questions') }}
                </a>
            </div>
        </div>

        @if ($statusMessage !== '')
            <div class="rounded-xl border border-teal/30 bg-teal/10 px-4 py-3 text-sm text-navy" role="status">{{ $statusMessage }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-2xl border border-border bg-white p-4 lg:col-span-1">
                <h2 class="text-sm font-semibold text-navy">{{ __('admin.bulk_focus_category') }}</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($categories as $category)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm">
                            <input type="radio" wire:model.live="focusCategory" value="{{ $category->value }}" class="rounded border-border text-accent focus:ring-accent">
                            <span>{{ $category->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-navy-muted">{{ __('admin.bulk_eligible') }}</dt><dd class="font-semibold text-navy">{{ $preview['eligible'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-navy-muted">{{ __('admin.bulk_would_change') }}</dt><dd class="font-semibold text-navy">{{ $preview['would_change'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-navy-muted">{{ __('admin.bulk_already_approved') }}</dt><dd class="font-semibold text-navy">{{ $preview['already_approved'] }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-navy-muted">{{ __('admin.bulk_selected') }}</dt><dd class="font-semibold text-navy">{{ count($selectedIds) }}</dd></div>
                </dl>

                <label class="mt-4 flex items-start gap-2 text-sm text-navy-muted">
                    <input type="checkbox" wire:model.live="overrideApproved" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.bulk_override_approved') }}</span>
                </label>

                <div class="mt-4 flex flex-col gap-2">
                    <button type="button" wire:click="selectEligiblePage" class="rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_select_page') }}</button>
                    <button type="button" wire:click="clearSelection" class="rounded-lg border border-border px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.bulk_clear_selection') }}</button>
                </div>

                <label class="mt-4 flex items-start gap-2 text-sm text-navy">
                    <input type="checkbox" wire:model="confirmBulk" class="mt-1 rounded border-border text-accent focus:ring-accent">
                    <span>{{ __('admin.bulk_confirm_label') }}</span>
                </label>
                @error('confirmBulk') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @error('selectedIds') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                <button
                    type="button"
                    wire:click="approveSelected"
                    wire:confirm="{{ __('admin.bulk_confirm_dialog') }}"
                    class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white"
                >
                    {{ __('admin.bulk_approve_selected') }}
                </button>
                <p class="mt-3 text-xs text-navy-muted">{{ __('admin.bulk_does_not_approve_rights') }}</p>
            </section>

            <section class="space-y-3 lg:col-span-2">
                @forelse ($entries as $entry)
                    <article class="rounded-2xl border border-border bg-white p-4">
                        <div class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                class="mt-1 rounded border-border text-accent focus:ring-accent"
                                wire:click="toggleSelect({{ $entry->id }})"
                                @checked(in_array($entry->id, $selectedIds, true))
                            >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-navy-muted">
                                    <span>#{{ $entry->id }}</span>
                                    <span>{{ $entry->source_group }}</span>
                                    <span>Q{{ $entry->original_question_number }}</span>
                                    <span>{{ __('admin.proposed') }}: {{ $entry->proposed_exam_category ?? '—' }}</span>
                                    <span>{{ $entry->classification_approved ? __('admin.approved') : __('admin.pending') }}</span>
                                </div>
                                <p class="mt-2 text-sm font-medium text-navy break-words">{{ $entry->english_question }}</p>
                                <p class="mt-1 text-xs text-navy-muted">
                                    {{ __('admin.answer') }}:
                                    {{ $entry->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}
                                    · {{ __('admin.proposal_source') }}: {{ $entry->classification_proposal_source ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl border border-border bg-white p-6 text-sm text-navy-muted">{{ __('admin.no_questions') }}</p>
                @endforelse

                <div class="pt-2">
                    {{ $entries->links() }}
                </div>
            </section>
        </div>
    </div>
</div>
