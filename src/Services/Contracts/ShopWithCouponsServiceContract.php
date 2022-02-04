<?php

namespace EscolaLms\Vouchers\Services\Contracts;

use EscolaLms\Cart\Services\Contracts\ShopServiceContract;
use EscolaLms\Vouchers\Models\Coupon;
use Treestoneit\ShoppingCart\Models\CartItem;

interface ShopWithCouponsServiceContract extends ShopServiceContract
{
    public function discount(?int $taxRate = null): int;
    public function totalWithoutDiscount(?int $taxRate = null): int;
    public function taxForItem(CartItem $item, ?int $taxRate = null): int;
    public function setCoupon(?Coupon $coupon): self;
    public function getCoupon(): ?Coupon;
}
