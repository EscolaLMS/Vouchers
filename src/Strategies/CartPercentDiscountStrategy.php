<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class CartPercentDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        $shopService = new ShopService($cart);
        return round($this->coupon->amount * ((int) $shopService->subtotal() + (int) $shopService->tax($taxRate)) / 100);
    }
}
