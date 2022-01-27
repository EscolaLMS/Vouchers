<?php

namespace EscolaLms\Vouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponProduct extends Model
{
    public $guarded = ['id'];

    protected $casts = [
        'excluded' => 'bool'
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }
}
