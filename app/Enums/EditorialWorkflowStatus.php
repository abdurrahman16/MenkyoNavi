<?php

namespace App\Enums;

enum EditorialWorkflowStatus: string
{
    case NeedsReview = 'needs_review';
    case InReview = 'in_review';
    case Classified = 'classified';
    case Blocked = 'blocked';
    case ReadyForTranslation = 'ready_for_translation';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs review',
            self::InReview => 'In review',
            self::Classified => 'Classified',
            self::Blocked => 'Blocked',
            self::ReadyForTranslation => 'Ready for translation',
        };
    }
}
