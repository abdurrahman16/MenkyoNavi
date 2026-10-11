<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Draft = 'draft';
    case Demo = 'demo';
    case Reviewed = 'reviewed';
    case Published = 'published';
    case Archived = 'archived';
}
