<?php

namespace App\Exceptions\Http;

class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Unauthenticated.')
    {
        parent::__construct($message, 401);
    }
}
