<?php

namespace EscolaLms\Vouchers\Services\Contracts;

use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

interface CouponsServiceContract
{
    public function getDiscountStrategyForCoupon(?Coupon $coupon): DiscountStrategyContract;

    public function couponCanBeUsedOnCart(Coupon $coupon, Cart $cart): bool;
    public function couponIsActive(Coupon $coupon): bool;
    public function couponInPriceRange(Coupon $coupon, int $price): bool;
    public function cartContainsItemsIncludedInCoupon(Coupon $coupon, Cart $cart): bool;
    public function cartContainsItemsNotExcludedFromCoupon(Coupon $coupon, Cart $cart): bool;
    public function couponTimesUsed(Coupon $coupon): int;
    public function couponTimesUsedByUser(Coupon $coupon, ?User $user = null): int;
}
