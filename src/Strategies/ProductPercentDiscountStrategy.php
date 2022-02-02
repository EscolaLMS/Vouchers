<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Strategies\Abstracts\DiscountStrategy;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Treestoneit\ShoppingCart\Models\CartItem;

class ProductPercentDiscountStrategy extends DiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(Cart $cart, ?int $taxRate = null): int
    {
        return (int) round($cart->items->reduce(fn (float $sum, CartItem $item) => $this->coupon->includedProducts->contains(
            fn (CouponProduct $product) => $product->product_type === $item->buyable_type && $product->product_id === $item->buyable_id
        ) ? ($sum + ($this->coupon->value * $item->getSubtotalAttribute() / 100)) : $sum, 0.0), 0);
    }
}
