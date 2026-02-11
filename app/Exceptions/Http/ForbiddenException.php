<?php

namespace App\Exceptions\Http;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Forbidden.')
    {
        parent::__construct($message, 403);
    }
}
