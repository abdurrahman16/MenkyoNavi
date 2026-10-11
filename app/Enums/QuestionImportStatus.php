<?php

namespace App\Enums;

enum QuestionImportStatus: string
{
    case Staged = 'staged';
    case FlaggedForReview = 'flagged_for_review';
    case NeedsMediaReview = 'needs_media_review';
    case Discarded = 'discarded';
}
