<?php

namespace App\Helpers;

class ApiReturn
{
    /**
     * Generate a success response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $httpCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(string $message, $data = [], int $httpCode = 200)
    {   
        if($data === null || empty($data)){
            $data = [];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'time' => $this->getExecutionTime(),
        ], $httpCode, [], JSON_PRETTY_PRINT);
    }

    /**
     * Generate an error response.
     *
     * @param string $message
     * @param int $httpCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function error(string $message, $data = [], int $httpCode = 400)
    {   
        if($data === null || empty($data)){
            $data = [];
        }
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'time' => $this->getExecutionTime(),
        ], $httpCode, [], JSON_PRETTY_PRINT);
    }

    /**
     * Get the query execution time.
     *
     * @return float
     */
    private function getExecutionTime(): float
    {
        return round(microtime(true) - LARAVEL_START, 3);
    }
}
