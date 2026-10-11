<?php

namespace App\Exceptions\Exams;

use Exception;

class AnswerLockedException extends Exception
{
    public function __construct(string $message = 'This answer is locked and cannot be changed.')
    {
        parent::__construct($message);
    }
}
