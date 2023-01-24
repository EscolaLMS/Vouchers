<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\CartItem;
use EscolaLms\Vouchers\Services\Contracts\CouponServiceContract;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class CartFixedDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateAdditionalDiscount(Cart $cart): int
    {
        return 0;
    }

    public function calculateDiscountForItem(Cart $cart, CartItem $cartItem): int
    {
        if (app(CouponServiceContract::class)->cartItemIsExcludedFromCoupon($this->coupon, $cartItem)) {
            return 0;
        }

        $totalAmount = 0;
        $maxAmount = $this->coupon->amount;

        foreach ($cart->items as $item) {
            $totalAmount += $item->basePrice;
        }

        $maxAmount = min($totalAmount, $maxAmount);

        return round($cartItem->basePrice / $totalAmount * $maxAmount);
    }
}
