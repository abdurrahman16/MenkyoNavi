<?php

namespace App\Enums;

enum TranslationReviewStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case NeedsRevision = 'needs_revision';
}
