<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Treestoneit\ShoppingCart\Models\CartItem;

class ProductPercentDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        $shopService = new ShopService($cart);
        return $cart->itemsIncludedInCoupon()->sum(fn (CartItem $item) => $this->coupon->amount * ($item->subtotal + $shopService->taxForItem($item, $taxRate)) / 100);
    }
}
