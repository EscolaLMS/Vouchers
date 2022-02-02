<?php

namespace EscolaLms\Vouchers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Treestoneit\ShoppingCart\Models\Cart as BaseCart;

class Cart extends BaseCart
{
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
