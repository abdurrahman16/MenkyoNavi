<?php

namespace App\Services\Exams;

readonly class ExamPoolAvailability
{
    public function __construct(
        public bool $canStart,
        public int $availableStandard,
        public int $availableIllustrated,
        public int $requiredStandard,
        public int $requiredIllustrated,
        public int $questionCount,
        public int $maxScore,
        public ?string $message,
    ) {}
}
