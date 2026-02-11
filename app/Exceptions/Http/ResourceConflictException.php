<?php

namespace App\Exceptions\Http;

class ResourceConflictException extends ApiException
{
    public function __construct(string $message = 'Resource conflict.')
    {
        parent::__construct($message, 409);
    }
}
