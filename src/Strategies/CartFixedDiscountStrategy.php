<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\CartItem;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class CartFixedDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateAdditionalDiscount(Cart $cart): int
    {
        return $cart->totalPreDiscount < $this->coupon->amount ? $cart->totalPreDiscount : $this->coupon->amount;
    }

    public function calculateDiscountForItem(Cart $cart, CartItem $cartItem): int
    {
        return 0;
    }
}
