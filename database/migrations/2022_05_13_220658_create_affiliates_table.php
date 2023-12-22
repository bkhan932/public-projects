<?php

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
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('merchant_id');
            /*
            We souldn't use float data type, as commission rate will store percentage,
            and percentage mostly has two decimal places after the point,
            and float can store more than decimal places after the point. Also I have taken the commission rate as unsigned so no
            negative values can be stored as percentage is not negative.
            */
            $table->unsignedDecimal('commission_rate', 5, 2);
            $table->string('discount_code');
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
        Schema::dropIfExists('affiliates');
    }
};
