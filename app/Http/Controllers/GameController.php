<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Wallet\DepositAction;
use App\Actions\Wallet\PlaceBetAction;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\PlaceBetRequest;
use App\Http\Resources\BetResultResource;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function __construct(
        private readonly DepositAction $depositAction,
        private readonly PlaceBetAction $placeBetAction,
    ) {}

    public function spin(PlaceBetRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $betAmount = number_format((float) $request->validated('bet_amount'), 4, '.', '');
            $result = $this->placeBetAction->execute($user, $betAmount);

            $resource = new BetResultResource($result);

            return response()->json([
                'success' => true,
                'data' => $resource->toArray($request),
            ]);
        } catch (InsufficientBalanceException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $depositAmount = number_format((float) $request->validated('amount'), 4, '.', '');
        $result = $this->depositAction->execute($user, $depositAmount);

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
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }
}
