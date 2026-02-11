<?php

namespace App\Exceptions\Http;

class BusinessValidationException extends ApiException
{
    public function __construct(string $message = 'Validation failed.')
    {
        parent::__construct($message, 422);
    }
}
