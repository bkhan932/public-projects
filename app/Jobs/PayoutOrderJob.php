<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {
    }

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        try {
            $apiService->sendPayout($this->order->affiliate->user->email, $this->order->commission_owed);
            $this->order->update(['payout_status' => Order::STATUS_PAID]);
        } catch (RuntimeException $exception) {
            //$this->order->update(['payout_status' => Order::STATUS_UNPAID]);
            $this->recordFailedJob($exception);
            throw $exception;
        }
    }

    /**
     * This function is useful for future references to the failed payouts, when the payout fails so we can have some info
     * about the failed jobs.
     *
     * @param \RuntimeException $exception
     * @return void
     */
    protected function recordFailedJob(RuntimeException $exception)
    {
        $payload = json_encode($this->order->toArray());

        $failedJobData = [
            'uuid' => $this->order->uuid,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ];

        DB::table('failed_jobs')->insert($failedJobData);

        Log::error("Failed PayoutOrderJob: " . $exception->getMessage(), $failedJobData);
    }
}
