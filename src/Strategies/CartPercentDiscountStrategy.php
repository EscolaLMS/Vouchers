<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Treestoneit\ShoppingCart\Models\CartItem;

class CartPercentDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        $shopService = new ShopService($cart);
        if ($this->coupon->excludedProducts()->count() === 0) {
            return round($this->coupon->amount * ((int) $shopService->subtotal() + (int) $shopService->tax($taxRate)) / 100);
        }
        return round($this->coupon->amount * ($this->subtotal($cart) + $this->tax($shopService, $taxRate)) / 100);
    }

    private function subtotal(Cart $cart): int
    {
        return $cart->itemsWithoutExcludedFromCoupon()->sum(fn (CartItem $item) => $item->subtotal);
    }

    private function tax(ShopService $shopService, ?int $taxRate = null): int
    {
        return $shopService->getCart()->itemsWithoutExcludedFromCoupon()->sum(fn (CartItem $item) => $shopService->taxForItem($item, $taxRate));
    }
}
