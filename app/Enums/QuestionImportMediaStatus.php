<?php

namespace App\Enums;

enum QuestionImportMediaStatus: string
{
    case None = 'none';
    case Available = 'available';
    case MissingSignKey = 'missing_sign_key';
    case NeedsReview = 'needs_review';
}
