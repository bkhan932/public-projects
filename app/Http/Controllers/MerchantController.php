<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Auth\AuthenticationException;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService
    ) {
    }

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        try {
            $user = auth()->user();
            if ($user) {

                $stats = $this->merchantService->getOrderStats($from, $to, $user->merchant->id);

                return response()->json($stats);
            } else
                return response()->json(['error' => 'Unauthorized'], 401);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
