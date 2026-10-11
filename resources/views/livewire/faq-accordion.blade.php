<div
    class="divide-y divide-border rounded-3xl border border-border bg-white shadow-soft"
    x-data="{ open: null }"
>
    @forelse ($items as $index => $item)
        @php
            $question = is_array($item) ? ($item['question'] ?? $item['q'] ?? '') : ($item->question ?? '');
            $answer = is_array($item) ? ($item['answer'] ?? $item['a'] ?? '') : ($item->answer ?? '');
            $panelId = 'faq-panel-' . $this->getId() . '-' . $index;
            $buttonId = 'faq-button-' . $this->getId() . '-' . $index;
        @endphp
        <div class="px-2 sm:px-4">
            <h3 class="m-0">
                <button
                    type="button"
                    id="{{ $buttonId }}"
                    class="flex w-full items-center justify-between gap-4 py-5 text-left text-base font-semibold text-navy transition hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2"
                    :aria-expanded="open === {{ $index }}"
                    aria-controls="{{ $panelId }}"
                    @click="open = open === {{ $index }} ? null : {{ $index }}"
                >
                    <span>{{ $question }}</span>
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface text-accent transition-transform"
                        aria-hidden="true"
                        :class="open === {{ $index }} ? 'rotate-180' : ''"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </button>
            </h3>
            <div
                id="{{ $panelId }}"
                role="region"
                aria-labelledby="{{ $buttonId }}"
                x-show="open === {{ $index }}"
                x-cloak
                class="pb-5 text-sm leading-relaxed text-navy-muted"
            >
                {!! nl2br(e($answer)) !!}
            </div>
        </div>
    @empty
        <div class="p-6">
            <x-ui.empty-state
                title="No FAQ entries yet"
                description="Questions and answers will appear here once configured."
            />
        </div>
    @endforelse
</div>
