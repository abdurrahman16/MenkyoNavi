<?php

namespace App\Enums;

enum QuestionType: string
{
    case StandardTrueFalse = 'standard_true_false';
    case IllustratedGroup = 'illustrated_group';
}
