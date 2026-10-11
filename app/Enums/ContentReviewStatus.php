<?php

namespace App\Enums;

enum ContentReviewStatus: string
{
    case Pending = 'pending';
    case NeedsRevision = 'needs_revision';
    case Reviewed = 'reviewed';
}
