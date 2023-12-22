<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('affiliate_id')->nullable()->constrained();
            $table->string('customer_email')->nullable();
            $table->string('external_order_id')->nullable();
            /*
            The subtotal will store price/amount, and mostly price has two decimal places after the point, that why we
            should choose the decimal data type instead of the float, for decimals we have control how many decimal places it will have
            after the point.
            Same goes for the commission_owed.

            In short decimal data type is prefered for monetary values instead of using float.
            */
            $table->decimal('subtotal', 10, 2);
            $table->decimal('commission_owed', 10, 2)->default(0.00);
            $table->string('payout_status')->default(Order::STATUS_UNPAID);
            $table->string('discount_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
