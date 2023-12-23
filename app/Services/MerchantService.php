<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

class MerchantService
{
    /**
     * Get order statistics for the merchant.
     *
     * @param  string $from
     * @param  string $to
     * @return array
     */
    public function getOrderStats(string $from, string $to): array
    {
        $orders = Order::whereBetween('created_at', [$from, $to])->get();

        $count = $orders->count();
        $commissionsWithoutAffiliate = $orders->whereNull('affiliate_id')->sum('commission_owed');
        $commissionOwed = $orders->sum('commission_owed');
        $revenue = $orders->sum('subtotal');

        return [
            'count' => $count,
            'commissions_owed' => $commissionOwed - $commissionsWithoutAffiliate,
            'revenue' => $revenue,
        ];
    }

    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['api_key'],
                'type' => User::TYPE_MERCHANT,
            ]);

            if (!$user) {
                throw new \Exception("User creation failed");
            }

            return Merchant::create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'display_name' => $data['name'],
                'default_commission_rate' => 0.1
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
        ]);

        $user->merchant->update([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->first();

        return $user ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();

        foreach ($unpaidOrders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }
}
