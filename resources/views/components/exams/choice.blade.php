@props([
    'group',
    'questionId',
    'answerSlot' => 0,
    'selected' => null,
    'label' => null,
    'locked' => false,
])

@php
    $label = $label ?? __('exams.true_or_false');
    $isLocked = (bool) $locked || $selected !== null;
@endphp

<div class="exam-choice-grid grid grid-cols-1 gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ $label }}">
    <label
        @class(['min-w-0', 'pointer-events-none opacity-95' => $isLocked && $selected !== true])
        @unless ($isLocked)
            wire:click="selectAnswer({{ (int) $questionId }}, {{ (int) $answerSlot }}, true)"
        @endunless
    >
        <input
            type="radio"
            name="{{ $group }}"
            value="1"
            class="peer sr-only"
            @checked($selected === true)
            @disabled($isLocked)
            wire:loading.attr="disabled"
            wire:target="selectAnswer, confirmIllustratedGroup"
        >
        <span @class([
            'exam-choice exam-choice--true peer-focus-visible:ring-2 peer-focus-visible:ring-matcha peer-focus-visible:ring-offset-2',
            'exam-choice--selected answer-choice-selected' => $selected === true,
            'exam-choice--locked' => $isLocked,
        ])>
            <span aria-hidden="true" class="exam-choice__icon">○</span>
            <span class="exam-choice__text">{{ __('exams.true') }}</span>
            @if ($selected === true)
                <span class="exam-choice__check" aria-hidden="true">✓</span>
            @endif
        </span>
    </label>
    <label
        @class(['min-w-0', 'pointer-events-none opacity-95' => $isLocked && $selected !== false])
        @unless ($isLocked)
            wire:click="selectAnswer({{ (int) $questionId }}, {{ (int) $answerSlot }}, false)"
        @endunless
    >
        <input
            type="radio"
            name="{{ $group }}"
            value="0"
            class="peer sr-only"
            @checked($selected === false)
            @disabled($isLocked)
            wire:loading.attr="disabled"
            wire:target="selectAnswer, confirmIllustratedGroup"
        >
        <span @class([
            'exam-choice exam-choice--false peer-focus-visible:ring-2 peer-focus-visible:ring-vermilion peer-focus-visible:ring-offset-2',
            'exam-choice--selected answer-choice-selected' => $selected === false,
            'exam-choice--locked' => $isLocked,
        ])>
            <span aria-hidden="true" class="exam-choice__icon">×</span>
            <span class="exam-choice__text">{{ __('exams.false') }}</span>
            @if ($selected === false)
                <span class="exam-choice__check" aria-hidden="true">✓</span>
            @endif
        </span>
    </label>
</div>
