<?php

namespace App\Enums;

enum StagedTranslationStatus: string
{
    case PendingTranslation = 'pending_translation';
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case Reviewed = 'reviewed';
    case RegulatoryVerificationPending = 'regulatory_verification_pending';
    case ReadyForPublication = 'ready_for_publication';

    public function isReviewedOrHigher(): bool
    {
        return in_array($this, [
            self::Reviewed,
            self::RegulatoryVerificationPending,
            self::ReadyForPublication,
        ], true);
    }

    public function allowsOverwriteWithoutForce(): bool
    {
        return ! $this->isReviewedOrHigher();
    }
}
