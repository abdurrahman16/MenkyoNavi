<?php

namespace App\Enums;

enum QuestionMediaType: string
{
    case Illustration = 'illustration';
    case RoadSign = 'road_sign';
    case Hazard = 'hazard';
    case Other = 'other';
}
