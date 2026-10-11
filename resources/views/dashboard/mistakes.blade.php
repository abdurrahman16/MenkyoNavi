<x-layouts.dashboard>
    <x-slot:header>
        <h1 class="text-2xl font-bold text-navy">{{ __('mistakes.title') }}</h1>
    </x-slot:header>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($groups->isEmpty())
                <x-ui.empty-state
                    :title="__('mistakes.empty')"
                    :description="__('mistakes.japanese_source')"
                >
                    <a href="{{ route('exams.index') }}" class="btn-primary mt-6">{{ __('home.explore_exam_modes') }}</a>
                </x-ui.empty-state>
            @else
                <p class="text-sm text-navy-muted">
                    {{ __('mistakes.japanese_source') }}
                    <a href="{{ route('exams.attempts.index') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('exams.practice_history') }}</a>
                </p>
                @foreach ($groups as $category => $mistakes)
                    <section class="space-y-4" aria-labelledby="mistakes-{{ $loop->index }}">
                        <h2 id="mistakes-{{ $loop->index }}" class="text-lg font-bold text-navy">{{ $category }}</h2>
                        @foreach ($mistakes as $mistake)
                            @php
                                $locale = app()->getLocale();
                                $translation = $locale === 'ja'
                                    ? null
                                    : $mistake->question->translations->firstWhere('locale', $locale);
                            @endphp
                            <article class="rounded-2xl border border-border bg-white p-5 shadow-soft">
                                @if ($locale === 'ja' || $translation === null)
                                    @if ($locale !== 'ja' && $translation === null)
                                        <p class="mb-2 text-xs font-semibold text-navy">{{ __('exams.translation_unavailable') }}</p>
                                        <p class="mb-2 text-xs text-navy-muted">{{ __('exams.source_japanese_label') }}</p>
                                    @endif
                                    <p class="text-base leading-relaxed text-navy" lang="ja">{{ $mistake->question->japanese_text }}</p>
                                @else
                                    <p class="text-base leading-relaxed text-navy" lang="{{ $locale }}">{{ $translation->question_text }}</p>
                                @endif
                                <p class="mt-3 text-sm leading-relaxed text-navy-muted">
                                    <span class="font-semibold text-navy">{{ __('results.explanation') }}. </span>
                                    @if ($locale === 'ja')
                                        {{ $mistake->question->explanation_ja ?: __('common.pending_editorial_review') }}
                                    @else
                                        {{ $translation?->explanation ?: ($mistake->question->explanation_ja ?: __('common.pending_editorial_review')) }}
                                    @endif
                                </p>
                                <p class="mt-3 text-xs text-navy-muted">
                                    {{ $mistake->incorrect_count }}
                                    · {{ $mistake->last_incorrect_at->timezone(config('app.timezone'))->format('Y-m-d') }}
                                </p>
                                @if ($mistake->lastExamAttempt && $mistake->lastExamAttempt->user_id === auth()->id() && $mistake->lastExamAttempt->status->revealsAnswers())
                                    <a href="{{ route('exams.attempts.review', $mistake->lastExamAttempt) }}" class="mt-3 inline-flex text-sm font-semibold text-accent hover:text-accent-dark">
                                        {{ __('mistakes.review_again') }}
                                    </a>
                                @endif
                            </article>
                        @endforeach
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</x-layouts.dashboard>
