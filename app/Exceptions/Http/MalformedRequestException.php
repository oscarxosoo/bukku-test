<?php

namespace App\Exceptions\Http;

class MalformedRequestException extends ApiException
{
    public function __construct(string $message = 'Bad request.')
    {
        parent::__construct($message, 400);
    }
}
