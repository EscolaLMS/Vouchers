<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class NoneDiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        return 0;
    }
}
