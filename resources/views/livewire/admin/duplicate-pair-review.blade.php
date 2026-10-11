<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <a href="{{ route('admin.questions.dashboard') }}" class="text-sm font-medium text-navy-muted hover:text-navy">← {{ __('admin.dashboard') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-navy">{{ __('admin.duplicate_review_title') }}</h1>
            <p class="mt-1 text-sm text-navy-muted">{{ __('admin.duplicate_review_subtitle') }}</p>
        </div>

        @if ($statusMessage)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ $statusMessage }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="rounded-2xl border border-border bg-white p-4">
                <h2 class="text-sm font-semibold text-navy">{{ __('admin.pairs') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($reviews as $review)
                        <li>
                            <button
                                type="button"
                                wire:click="selectReview({{ $review->id }})"
                                class="w-full rounded-lg px-3 py-2 text-left {{ $selectedReviewId === $review->id ? 'bg-navy text-white' : 'bg-surface text-navy' }}"
                            >
                                #{{ $review->entry_id }} ↔ #{{ $review->related_entry_id }}
                                <span class="block text-xs opacity-80">{{ $review->decision->label() }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="text-navy-muted">{{ __('admin.no_similar_pairs') }}</li>
                    @endforelse
                </ul>
            </aside>

            <section class="space-y-4">
                @if ($selected)
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ([$selected->entry, $selected->relatedEntry] as $side)
                            <article class="rounded-2xl border border-border bg-white p-5 text-sm">
                                <h2 class="font-semibold text-navy">{{ __('admin.entry_title', ['id' => $side->id]) }}</h2>
                                <p class="mt-2 text-xs text-navy-muted">
                                    {{ __('admin.source_filename', ['filename' => $side->batch?->source_filename, 'key' => $side->batch?->source_key]) }}<br>
                                    {{ __('admin.group_meta', ['group' => $side->source_group, 'number' => $side->original_question_number]) }}
                                </p>
                                <p class="mt-3 whitespace-pre-wrap font-medium text-navy">{{ $side->english_question }}</p>
                                <p class="mt-2"><span class="text-navy-muted">{{ __('admin.answer') }}:</span> {{ $side->correct_answer ? __('admin.answer_true') : __('admin.answer_false') }}</p>
                                <p class="mt-2 whitespace-pre-wrap text-navy-muted">{{ $side->english_explanation }}</p>
                            </article>
                        @endforeach
                    </div>

                    <form wire:submit="saveDecision" class="rounded-2xl border border-border bg-white p-5 space-y-3">
                        <p class="text-sm"><span class="font-medium text-navy">{{ __('admin.similarity_reason') }}</span> {{ $selected->similarity_reason }}</p>
                        <label class="block text-sm">
                            <span class="font-medium text-navy">{{ __('admin.decision') }}</span>
                            <select wire:model="decision" class="mt-1 w-full rounded-lg border-border">
                                @foreach ($decisions as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="font-medium text-navy">{{ __('admin.reviewer_notes') }}</span>
                            <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-lg border-border"></textarea>
                        </label>
                        @if ($selected->reviewer)
                            <p class="text-xs text-navy-muted">{{ __('admin.last_reviewed_by', ['email' => $selected->reviewer->email, 'time' => $selected->reviewed_at]) }}</p>
                        @endif
                        <button type="submit" class="rounded-lg bg-navy px-3 py-2 text-sm font-semibold text-white">{{ __('admin.save_decision') }}</button>
                    </form>
                @else
                    <div class="rounded-2xl border border-dashed border-border bg-white p-8 text-sm text-navy-muted">
                        {{ __('admin.select_pair_prompt') }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
