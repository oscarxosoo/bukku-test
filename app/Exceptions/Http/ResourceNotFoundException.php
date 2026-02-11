<?php

namespace App\Exceptions\Http;

class ResourceNotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message, 404);
    }
}
