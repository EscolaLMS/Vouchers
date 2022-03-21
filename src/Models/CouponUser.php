<?php

namespace EscolaLms\Vouchers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * EscolaLms\Vouchers\Models\CouponUser
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \EscolaLms\Vouchers\Models\Coupon|null $coupon
 * @property-read \EscolaLms\Vouchers\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponUser whereUserId($value)
 * @mixin \Eloquent
 */
class CouponUser extends Pivot
{
    public $guarded = ['id'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
