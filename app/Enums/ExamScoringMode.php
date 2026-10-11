<?php

namespace App\Enums;

enum ExamScoringMode: string
{
    case Standard = 'standard';
    case AllOrNothing = 'all_or_nothing';
}
