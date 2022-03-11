<?php

namespace EscolaLms\Vouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EscolaLms\Vouchers\Models\CouponEmail
 *
 * @property int $id
 * @property int $coupon_id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \EscolaLms\Vouchers\Models\Coupon|null $coupon
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CouponEmail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CouponEmail extends Model
{
    public $guarded = ['id'];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
