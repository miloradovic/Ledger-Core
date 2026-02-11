<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(
        public readonly string $required,
        public readonly string $available
    ) {
        parent::__construct(
            "Insufficient balance. Required: {$required}, Available: {$available}"
        );
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient balance',
            'error' => [
                'required' => $this->required,
                'available' => $this->available,
            ],
        ], 422);
    }
}
