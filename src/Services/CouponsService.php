<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use EscolaLms\Vouchers\Strategies\NoneDiscountStrategy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Treestoneit\ShoppingCart\Models\CartItem;

class CouponsService implements CouponsServiceContract
{
    public function createCoupon(): Coupon
    {
    }

    public function getDiscountStrategyForCoupon(?Coupon $coupon): DiscountStrategyContract
    {
        if (is_null($coupon)) {
            return new NoneDiscountStrategy;
        }

        $className = 'EscolaLms\\Vouchers\\Strategies\\' . Str::studly($coupon->type) . 'DiscountStrategy';

        if (!class_exists($className)) {
            throw new \RuntimeException($className . ' strategy does not exist.');
        }

        return new $className($coupon);
    }

    public function couponCanBeUsedOnCart(Coupon $coupon, Cart $cart): bool
    {
        $shopService = new ShopService($cart);
        return $this->couponIsActive($coupon)
            && $this->couponInPriceRange($coupon, $shopService->totalWithoutDiscount())
            && $this->cartContainsItemsIncludedInCoupon($coupon, $cart)
            && $this->cartContainsItemsNotExcludedFromCoupon($coupon, $cart)
            && $this->userEmailIncludedInCoupon($coupon);
    }

    public function couponIsActive(Coupon $coupon): bool
    {
        return (is_null($coupon->active_from) || Carbon::now()->greaterThanOrEqualTo($coupon->active_from))
            && (is_null($coupon->active_to) || Carbon::now()->lessThanOrEqualTo($coupon->active_to))
            && (is_null($coupon->limit_usage) || $coupon->limit_usage > $this->couponTimesUsed($coupon))
            && (is_null($coupon->limit_per_user) || $coupon->limit_per_user > $this->couponTimesUsedByUser($coupon));
    }

    public function couponInPriceRange(Coupon $coupon, int $price): bool
    {
        return is_null($coupon->min_cart_price) || $price >= $coupon->min_cart_price;
    }

    public function cartContainsItemsIncludedInCoupon(Coupon $coupon, Cart $cart): bool
    {
        return ($coupon->includedProducts()->count() === 0)
            || $cart->items->contains(
                fn (CartItem $item) => $coupon->includedProducts->contains(
                    fn (CouponProduct $product) => $product->product_type === $item->buyable_type && $product->product_id === $item->buyable_id
                )
            );
    }

    public function cartContainsItemsNotExcludedFromCoupon(Coupon $coupon, Cart $cart): bool
    {
        return ($coupon->excludedProducts()->count() === 0)
            || $cart->items->contains(
                fn (CartItem $item) => $coupon->excludedProducts->contains(
                    fn (CouponProduct $product) => $product->product_type !== $item->buyable_type || $product->product_id !== $item->buyable_id
                )
            );
    }

    public function couponTimesUsed(Coupon $coupon): int
    {
        return $coupon->orders_count ?? $coupon->orders()->count();
    }

    public function couponTimesUsedByUser(Coupon $coupon, ?User $user = null): int
    {
        if (empty($user)) {
            /** @var User $user */
            $user = Auth::user();
        }
        return $coupon->orders()->where('user_id', $user->getKey())->count();
    }

    public function userEmailIncludedInCoupon(Coupon $coupon, ?User $user = null): bool
    {
        if (empty($user)) {
            /** @var User $user */
            $user = Auth::user();
        }
        return $coupon->emails()->where('email', $user->email)->exists();
    }
}
