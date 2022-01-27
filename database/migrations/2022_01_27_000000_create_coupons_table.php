2<?php

use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('code')->unique();

            $table->string('type')->default(CouponTypeEnum::PRODUCT_PERCENT);

            $table->boolean('exclusive')->default(false);

            $table->datetime('active_from')->nullable();
            $table->datetime('active_to')->nullable();

            $table->unsignedInteger('limit_usage')->nullable();
            $table->unsignedInteger('limit_per_user')->nullable();

            $table->unsignedInteger('min_cart_price')->nullable();
            $table->unsignedInteger('max_cart_price')->nullable();

            $table->unsignedInteger('amount');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupons');
    }
}
