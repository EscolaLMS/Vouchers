<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Treestoneit\ShoppingCart\Models\CartItem;

class ProductFixedDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        return $cart->itemsIncludedInCoupon()->sum(fn (CartItem $item) => $this->coupon->amount * $item->quantity);
    }
}
