<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Cart\Models\Product;
use EscolaLms\Core\Dtos\OrderDto;
use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Dtos\CouponSearchDto;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponCategory;
use EscolaLms\Vouchers\Models\CouponEmail;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Services\Contracts\CouponServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use EscolaLms\Vouchers\Strategies\NoneDiscountStrategy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Treestoneit\ShoppingCart\Models\CartItem;

class CouponService implements CouponServiceContract
{
    public function searchAndPaginateCoupons(CouponSearchDto $searchDto, ?OrderDto $orderDto = null): LengthAwarePaginator
    {
        $query = Coupon::query();

        if (!is_null($searchDto->getName())) {
            $query->where('name', 'LIKE', $searchDto->getName() . '%');
        }

        if (!is_null($searchDto->getCode())) {
            $query->where('code', 'LIKE', Str::upper($searchDto->getCode()) . '%');
        }

        if (!is_null($searchDto->getType())) {
            $query->where('type', '=', $searchDto->getType());
        }

        if (!is_null($searchDto->getActiveFrom())) {
            $query->whereDate('active_from', '>=', $searchDto->getActiveFrom())
                ->orWhere(
                    fn (Builder $subquery) =>
                    $subquery->whereNull('active_from')
                        ->whereDate('active_to', '>=', $searchDto->getActiveFrom())
                );
        }

        if (!is_null($searchDto->getActiveTo())) {
            $query->whereDate('active_to', '<=', $searchDto->getActiveFrom())
                ->orWhere(
                    fn (Builder $subquery) =>
                    $subquery->whereNull('active_to')
                        ->whereDate('active_from', '<=', $searchDto->getActiveFrom())
                );
        }

        if (!is_null($orderDto) && !is_null($orderDto->getOrder())) {
            $query = $query->orderBy($orderDto->getOrderBy(), $orderDto->getOrder());
        }

        return $query->paginate($searchDto->getPerPage() ?? 15);
    }

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
            CouponProduct::create([
                'coupon_id' => $coupon->getKey(),
                'product_id' => $product,
                'excluded' => false,
            ]);
        }
        foreach ($data['excluded_products'] ?? []  as $product) {
            CouponProduct::create([
                'coupon_id' => $coupon->getKey(),
                'product_id' => $product,
                'excluded' => true,
            ]);
        }
        foreach ($data['included_categories'] ?? [] as $category) {
            CouponCategory::create([
                'coupon_id' => $coupon->getKey(),
                'category_id' => $category,
                'excluded' => false,
            ]);
        }
        foreach ($data['excluded_categories'] ?? [] as $category) {
            CouponCategory::create([
                'coupon_id' => $coupon->getKey(),
                'category_id' => $category,
                'excluded' => true,
            ]);
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
        if (!isset($data['included_products'])) {
            $data['included_products'] = $coupon->includedProducts->pluck('id');
        }
        if (!isset($data['excluded_products'])) {
            $data['excluded_products'] = $coupon->excludedProducts->pluck('id');
        }
        if (!isset($data['included_categories'])) {
            $data['included_categories'] = $coupon->includedCategories->pluck('id');
        }
        if (!isset($data['excluded_categories'])) {
            $data['excluded_categories'] = $coupon->excludedCategories->pluck('id');
        }

        $syncIncludedProducts = collect($data['included_products'])
            ->mapWithKeys(fn ($id) => [$id => ['excluded' => false]]);
        $syncExcludedProducts = collect($data['excluded_products'])
            ->mapWithKeys(fn ($id) => [$id => ['excluded' => true]]);
        $coupon->products()->sync($syncIncludedProducts->merge($syncExcludedProducts));

        $syncIncludedCategories = collect($data['included_categories'])
            ->mapWithKeys(fn ($id) => [$id => ['excluded' => false]]);
        $syncExcludedCategories = collect($data['included_categories'])
            ->mapWithKeys(fn ($id) => [$id => ['excluded' => true]]);
        $coupon->categories()->sync($syncIncludedCategories->merge($syncExcludedCategories));

        if (isset($data['emails'])) {
            CouponEmail::where('coupon_id', $coupon->getKey())
                ->whereNotIn('email', $data['emails'])
                ->delete();
        }
        foreach ($data['emails'] ?? [] as $email) {
            CouponEmail::query()->firstOrCreate([
                'coupon_id' => $coupon->getKey(),
                'email' => $email,
            ]);
        }

        unset($data['emails']);
        unset($data['included_products']);
        unset($data['excluded_products']);
        unset($data['included_categories']);
        unset($data['excluded_categories']);

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
        $coupon->load('includedCategories', 'excludedCategories', 'includedProducts', 'excludedProducts');
        $cart->load('items', 'items.buyable');

        $cartManager = new CartManager($cart);

        return $this->couponIsActive($coupon)
            && $this->couponInPriceRange($coupon, $cartManager->totalPreAdditionalDiscount())
            && $this->cartContainsItemsIncludedInCoupon($coupon, $cart)
            && $this->cartContainsItemsNotExcludedFromCoupon($coupon, $cart)
            && $this->userEmailIncludedInCoupon($coupon);
    }

    public function couponIsActive(Coupon $coupon): bool
    {
        return $coupon->active
            && (is_null($coupon->active_from) || Carbon::now()->greaterThanOrEqualTo($coupon->active_from))
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
        return ($coupon->includedProducts()->count() === 0 && $coupon->includedCategories()->count() === 0) || $this->cartItemsIncludedInCoupon($coupon, $cart)->count() > 0;
    }

    public function cartItemsIncludedInCoupon(Coupon $coupon, Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $item) => $this->cartItemIsIncludedInCoupon($coupon, $item));
    }

    public function cartItemIsIncludedInCoupon(Coupon $coupon, CartItem $item): bool
    {
        return $item->buyable instanceof Product && ($this->productIsIncludedInCoupon($coupon, $item->buyable) || $this->productCategoriesAreIncludedInCoupon($coupon, $item->buyable));
    }

    public function productIsIncludedInCoupon(Coupon $coupon, Product $product): bool
    {
        return $coupon->includedProducts->contains($product);
    }

    public function productCategoriesAreIncludedInCoupon(Coupon $coupon, Product $product): bool
    {
        return $coupon->includedCategories->whereIn('id', $product->categories->pluck('id')->toArray())->count() > 0;
    }

    public function cartContainsItemsNotExcludedFromCoupon(Coupon $coupon, Cart $cart): bool
    {
        return ($coupon->excludedProducts()->count() === 0 && $coupon->excludedCategories()->count() === 0) || $this->cartItemsWithoutExcludedFromCoupon($coupon, $cart)->count() > 0;
    }

    public function cartItemsWithoutExcludedFromCoupon(Coupon $coupon, Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $item) => !$this->cartItemIsExcludedFromCoupon($coupon, $item));
    }

    public function cartItemIsExcludedFromCoupon(Coupon $coupon, CartItem $item): bool
    {
        return $item->buyable instanceof Product && ($this->productIsExcludedFromCoupon($coupon, $item->buyable) || $this->productCategoriesAreExcludedFromCoupon($coupon, $item->buyable));
    }

    public function productIsExcludedFromCoupon(Coupon $coupon, Product $product): bool
    {
        return $coupon->excludedProducts->contains($product);
    }

    public function productCategoriesAreExcludedFromCoupon(Coupon $coupon, Product $product): bool
    {
        return $coupon->excludedCategories->whereIn('id', $product->categories->pluck('id')->toArray())->count() > 0;
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
