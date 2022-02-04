<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponEmail;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use EscolaLms\Vouchers\Strategies\NoneDiscountStrategy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Treestoneit\ShoppingCart\Buyable;
use Treestoneit\ShoppingCart\Models\CartItem;

class CouponsService implements CouponsServiceContract
{
    public function createCoupon(array $data): Coupon
    {
        $coupon = new Coupon([
            'name' => $data['name'],
            'code' => $data['code'],
            'active_from' => $data['active_from'],
            'active_to' => $data['active_to'],
            'limit_usage' => $data['limit_usage'],
            'limit_per_user' => $data['limit_per_user'],
            'min_cart_price' => $data['min_cart_price'],
            'max_cart_price' => $data['max_cart_price'],
            'amount' => $data['amount'],
        ]);
        $coupon->save();

        foreach ($data['included_products'] ?? [] as $product) {
            if (is_a($product['class'], Buyable::class, true)) {
                CouponProduct::create([
                    'coupon_id' => $coupon->getKey(),
                    'product_id' => $product['id'],
                    'product_type' => $product['class'],
                    'excluded' => false,
                ]);
            }
        }
        foreach ($data['excluded_products'] ?? []  as $product) {
            if (is_a($product['class'], Buyable::class, true)) {
                CouponProduct::create([
                    'coupon_id' => $coupon->getKey(),
                    'product_id' => $product['id'],
                    'product_type' => $product['class'],
                    'excluded' => true,
                ]);
            }
        }
        foreach ($data['emails'] ?? []  as $email) {
            CouponEmail::create([
                'coupon_id' => $coupon->getKey(),
                'email' => $email,
            ]);
        }

        return $coupon->refresh();
    }

    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        if (isset($data['included_products'])) {
            CouponProduct::where('coupon_id', $coupon->getKey())->where('excluded', false)->delete();
        }
        foreach ($data['included_products'] ?? [] as $product) {
            if (is_a($product['class'], Buyable::class, true)) {
                CouponProduct::create([
                    'coupon_id' => $coupon->getKey(),
                    'product_id' => $product['id'],
                    'product_type' => $product['class'],
                    'excluded' => false,
                ]);
            }
        }
        if (isset($data['excluded_products'])) {
            CouponProduct::where('coupon_id', $coupon->getKey())->where('excluded', true)->delete();
        }
        foreach ($data['excluded_products'] ?? [] as $product) {
            if (is_a($product['class'], Buyable::class, true)) {
                CouponProduct::create([
                    'coupon_id' => $coupon->getKey(),
                    'product_id' => $product['id'],
                    'product_type' => $product['class'],
                    'excluded' => true,
                ]);
            }
        }
        if (isset($data['emails'])) {
            CouponEmail::where('coupon_id', $coupon->getKey())->delete();
        }
        foreach ($data['emails'] ?? [] as $email) {
            CouponEmail::create([
                'coupon_id' => $coupon->getKey(),
                'email' => $email,
            ]);
        }

        unset($data['emails']);
        unset($data['included_products']);
        unset($data['excluded_products']);

        $coupon->fill($data);
        $coupon->save();

        return $coupon->refresh();
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
            || $this->cartItemsIncludedInCoupon($coupon, $cart)->count() > 0;
    }

    public function cartContainsItemsNotExcludedFromCoupon(Coupon $coupon, Cart $cart): bool
    {
        return $coupon->excludedProducts()->count() === 0
            || $this->cartItemsWithoutExcludedFromCoupon($coupon, $cart)->count() > 0;
    }

    public function cartItemsIncludedInCoupon(Coupon $coupon, Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $item) => $coupon->includedProducts->contains(
            fn (CouponProduct $product) => $product->product_type === $item->buyable_type && $product->product_id === $item->buyable_id
        ));
    }

    public function cartItemsExcludedFromCoupon(Coupon $coupon, Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $item) => $coupon->excludedProducts->contains(
            fn (CouponProduct $product) => $product->product_type === $item->buyable_type && $product->product_id === $item->buyable_id
        ));
    }

    public function cartItemsWithoutExcludedFromCoupon(Coupon $coupon, Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $item) => !$coupon->excludedProducts->contains(
            fn (CouponProduct $product) => $product->product_type === $item->buyable_type && $product->product_id === $item->buyable_id
        ));
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
        return $coupon->emails->count() === 0 || $coupon->emails()->where('email', $user->email)->exists();
    }
}
