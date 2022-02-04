<?php

namespace EscolaLms\Vouchers\Strategies\Contracts;

use EscolaLms\Vouchers\Models\Cart;

interface DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int;
}
