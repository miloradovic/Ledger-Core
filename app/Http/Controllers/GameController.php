<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Wallet\DepositAction;
use App\Actions\Wallet\PlaceBetAction;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function __construct(
        private readonly DepositAction $depositAction,
        private readonly PlaceBetAction $placeBetAction
    ) {
    }

    public function spin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bet_amount' => 'required|numeric|min:0.01|max:1000',
        ]);

        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // @var User $user
        try {
            $result = $this->placeBetAction->execute($user, $validated['bet_amount']);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000',
        ]);

        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        /** @var User $user */
        $result = $this->depositAction->execute($user, $validated['amount']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function balance(): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'balance' => $user->balance,
        ]);
    }

    public function transactions(): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $transactions = $user->transactions()->latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }
}
