<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Treestoneit\ShoppingCart\Models\Cart as BaseCart;

class Cart extends BaseCart
{
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function itemsIncludedInCoupon(): Collection
    {
        if ($this->coupon) {
            return app(CouponsServiceContract::class)->cartItemsIncludedInCoupon($this->coupon, $this);
        }
        return $this->items;
    }

    public function itemsExcludedFromCoupon(): Collection
    {
        if ($this->coupon) {
            return app(CouponsServiceContract::class)->cartItemsExcludedFromCoupon($this->coupon, $this);
        }
        return Collection::empty();
    }

    public function itemsWithoutExcludedFromCoupon(): Collection
    {
        if ($this->coupon) {
            return app(CouponsServiceContract::class)->cartItemsWithoutExcludedFromCoupon($this->coupon, $this);
        }
        return $this->items;
    }
}
