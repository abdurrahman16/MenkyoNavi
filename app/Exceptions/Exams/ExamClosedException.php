<?php

namespace App\Exceptions\Exams;

use Exception;

class ExamClosedException extends Exception
{
    public function __construct(string $message = 'This examination is no longer accepting answers.')
    {
        parent::__construct($message);
    }
}
