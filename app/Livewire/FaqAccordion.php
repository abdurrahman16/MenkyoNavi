<?php

namespace App\Livewire;

use Livewire\Component;

class FaqAccordion extends Component
{
    /**
     * @var list<array{question: string, answer: string}>
     */
    public array $items = [];

    public function mount(?array $items = null): void
    {
        $this->items = $items ?? [
            [
                'question' => __('faq.q1'),
                'answer' => __('faq.a1'),
            ],
            [
                'question' => __('faq.q2'),
                'answer' => __('faq.a2'),
            ],
            [
                'question' => __('faq.q3'),
                'answer' => __('faq.a3'),
            ],
            [
                'question' => __('faq.q4'),
                'answer' => __('faq.a4'),
            ],
            [
                'question' => __('faq.q5'),
                'answer' => __('faq.a5'),
            ],
            [
                'question' => __('faq.q6'),
                'answer' => __('faq.a6'),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.faq-accordion');
    }
}
