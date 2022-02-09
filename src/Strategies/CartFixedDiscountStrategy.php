<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class CartFixedDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        return $this->coupon->amount;
    }
}
