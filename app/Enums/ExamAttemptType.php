<?php

namespace App\Enums;

enum ExamAttemptType: string
{
    case Demo = 'demo';
    case Full = 'full';
    case AdminPreview = 'admin_preview';
}
