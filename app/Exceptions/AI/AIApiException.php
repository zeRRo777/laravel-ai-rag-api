<?php

namespace App\Exceptions\AI;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AIApiException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 503, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'type' => config('app.url').'/errors/ai-service-unavailable',
            'title' => 'AI Service Unavailable',
            'status' => $this->code,
            'detail' => $this->message,
            'instance' => $request->getUri(),
        ], $this->code);
    }
}
