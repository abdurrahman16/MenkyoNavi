<x-layouts.dashboard :title="'Practice history | MenkyoNavi'">
    <x-slot:header>
        <h1 class="text-2xl font-bold text-navy">Practice history</h1>
    </x-slot:header>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($attempts->isEmpty())
            <x-ui.empty-state
                title="No practice sessions yet"
                description="When you finish a practice examination, the score and review link will be saved here."
            >
                <a href="{{ route('exams.index') }}" class="btn-primary mt-6">Browse exam modes</a>
            </x-ui.empty-state>
        @else
            <div class="space-y-3">
                @foreach ($attempts as $attempt)
                    <article class="rounded-2xl border border-border bg-white p-4 shadow-soft sm:flex sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-semibold text-navy">
                                {{ $attempt->examType->name }}
                                @if ($attempt->isDemo())
                                    <span class="ml-2 rounded-full bg-amber-soft px-2 py-0.5 text-xs font-semibold">Demo</span>
                                @endif
                            </h2>
                            <p class="mt-1 text-sm text-navy-muted">
                                {{ $attempt->started_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                · {{ str_replace('_', ' ', $attempt->status->value) }}
                                @if ($attempt->score !== null)
                                    · {{ $attempt->score }} / {{ $attempt->max_score }}
                                @endif
                            </p>
                        </div>
                        <div class="mt-3 sm:mt-0">
                            @if ($attempt->status === \App\Enums\ExamAttemptStatus::InProgress)
                                <a href="{{ route('exams.attempts.show', $attempt) }}" class="text-sm font-semibold text-accent hover:text-accent-dark">Continue</a>
                            @else
                                <a href="{{ route('exams.attempts.results', $attempt) }}" class="text-sm font-semibold text-accent hover:text-accent-dark">View result</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $attempts->links() }}</div>
        @endif
    </div>
</x-layouts.dashboard>
