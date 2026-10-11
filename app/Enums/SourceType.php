<?php

namespace App\Enums;

enum SourceType: string
{
    case OfficialGuide = 'official_guide';
    case Educational = 'educational';
    case InternalDemo = 'internal_demo';
    case Other = 'other';
}
