<?php

namespace App\Enums;

enum CopyrightReuseStatus: string
{
    case Unknown = 'unknown';
    case EducationalDemo = 'educational_demo';
    case Licensed = 'licensed';
    case PublicDomain = 'public_domain';
    case Restricted = 'restricted';
}
