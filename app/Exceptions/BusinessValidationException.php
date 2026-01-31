<?php

namespace App\Exceptions;

use Exception;

class BusinessValidationException extends Exception
{
    public function __construct(
        string $message,
        private string $field = 'general'
    ) {
        parent::__construct($message);
    }

    public function getField(): string
    {
        return $this->field;
    }
}
