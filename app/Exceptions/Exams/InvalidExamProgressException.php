<?php

namespace App\Exceptions\Exams;

use Exception;

class InvalidExamProgressException extends Exception
{
    public function __construct(string $message = 'That examination action is not allowed at this position.')
    {
        parent::__construct($message);
    }
}
