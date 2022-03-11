<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\CartItem;
use EscolaLms\Vouchers\Services\Contracts\CouponServiceContract;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class ProductFixedDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateAdditionalDiscount(Cart $cart): int
    {
        return 0;
    }

    public function calculateDiscountForItem(Cart $cart, CartItem $cartItem): int
    {
        if (!app(CouponServiceContract::class)->cartItemIncludedInCoupon($this->coupon, $cartItem)) {
            return 0;
        }
        return $this->coupon->amount;
    }
}
