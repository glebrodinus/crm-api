<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiReturn
{
    protected function success(string $message, mixed $data = [], int $httpCode = 200): JsonResponse
    {
        if ($data === null) {
            $data = [];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'time'    => $this->getExecutionTime(),
        ], $httpCode, [], $this->jsonOptions());
    }

    protected function error(string $message, mixed $data = [], int $httpCode = 400): JsonResponse
    {
        if ($data === null) {
            $data = [];
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data,
            'time'    => $this->getExecutionTime(),
        ], $httpCode, [], $this->jsonOptions());
    }

    private function getExecutionTime(): float
    {
        return round(microtime(true) - LARAVEL_START, 3);
    }

    private function jsonOptions(): int
    {
        return JSON_PRETTY_PRINT;
    }
}