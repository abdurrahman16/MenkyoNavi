<?php

namespace App\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case TimedOut = 'timed_out';
    case Abandoned = 'abandoned';

    public function isFinal(): bool
    {
        return $this !== self::InProgress;
    }

    public function revealsAnswers(): bool
    {
        return $this === self::Submitted || $this === self::TimedOut;
    }
}
