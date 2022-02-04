<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Cart\Models\Order as BaseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends BaseOrder
{
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
