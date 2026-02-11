<?php

namespace App\Exceptions\Http;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception implements ShouldntReport
{
    public function __construct(
        string $message,
        protected int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}