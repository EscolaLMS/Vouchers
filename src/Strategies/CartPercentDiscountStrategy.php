<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class CartPercentDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(ShopService $shopService, ?int $taxRate = null): int
    {
        return round($this->coupon->amount * ((int) $shopService->subtotal() + (int) $shopService->tax($taxRate)) / 100);
    }
}
