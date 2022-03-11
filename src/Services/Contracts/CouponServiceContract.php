<?php

namespace EscolaLms\Vouchers\Services\Contracts;

use EscolaLms\Cart\Models\Product as BaseProduct;
use EscolaLms\Core\Dtos\OrderDto;
use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Dtos\CouponSearchDto;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\CartItem;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CouponServiceContract
{
    public function searchAndPaginateCoupons(CouponSearchDto $searchDto, ?OrderDto $orderDto = null): LengthAwarePaginator;

    public function getDiscountStrategyForCoupon(?Coupon $coupon): DiscountStrategyContract;

    public function createCoupon(array $data): Coupon;
    public function updateCoupon(Coupon $coupon, array $data): Coupon;

    public function couponCanBeUsedOnCart(Coupon $coupon, Cart $cart): bool;
    public function couponIsActive(Coupon $coupon): bool;
    public function couponInPriceRange(Coupon $coupon, int $price): bool;
    public function cartContainsItemsIncludedInCoupon(Coupon $coupon, Cart $cart): bool;
    public function cartContainsItemsNotExcludedFromCoupon(Coupon $coupon, Cart $cart): bool;

    public function cartItemsIncludedInCoupon(Coupon $coupon, Cart $cart): Collection;
    public function cartItemsExcludedFromCoupon(Coupon $coupon, Cart $cart): Collection;
    public function cartItemsWithoutExcludedFromCoupon(Coupon $coupon, Cart $cart): Collection;

    public function productIncludedInCoupon(Coupon $coupon, BaseProduct $product): bool;
    public function productExcludedFromCoupon(Coupon $coupon, BaseProduct $product): bool;
    public function cartItemIncludedInCoupon(Coupon $coupon, CartItem $item): bool;
    public function cartItemExcludedFromCoupon(Coupon $coupon, CartItem $item): bool;

    public function couponTimesUsed(Coupon $coupon): int;
    public function couponTimesUsedByUser(Coupon $coupon, ?User $user = null): int;
}
