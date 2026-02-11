<?php

namespace App\Exceptions\Http;

class ServiceUnavailableException extends ApiException
{
    public function __construct(string $message = 'Service unavailable.')
    {
        parent::__construct($message, 503);
    }
}
