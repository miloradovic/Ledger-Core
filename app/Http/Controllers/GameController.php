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
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GameController extends Controller
{
    private const TRANSACTION_LIMIT = 50;

    public function __construct(
        private readonly DepositAction $depositAction,
        private readonly PlaceBetAction $placeBetAction,
    ) {}

    public function spin(PlaceBetRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
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
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
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
        $user = $this->authenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return response()->json([
            'success' => true,
            'balance' => $user->balance,
        ]);
    }

    public function transactions(): JsonResponse
    {
        $user = $this->authenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $transactions = $user->transactions()
            ->latest()
            ->limit(self::TRANSACTION_LIMIT)
            ->get(['id', 'type', 'amount', 'balance_after', 'created_at']);

        return response()->json([
            'success' => true,
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    private function authenticatedUser(): User|JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }
}
