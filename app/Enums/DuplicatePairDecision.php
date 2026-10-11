<?php

namespace App\Enums;

enum DuplicatePairDecision: string
{
    case SameMeaning = 'same_meaning';
    case DifferentMeaning = 'different_meaning';
    case NeedsAdditionalReview = 'needs_additional_review';

    public function label(): string
    {
        return match ($this) {
            self::SameMeaning => 'Same meaning',
            self::DifferentMeaning => 'Different meaning',
            self::NeedsAdditionalReview => 'Needs additional review',
        };
    }
}
