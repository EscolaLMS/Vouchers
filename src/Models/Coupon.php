<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use NumberFormatter;

class Coupon extends Model
{
    use SoftDeletes, HasFactory;

    public $guarded = ['id'];

    protected $casts = [
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function getValueStringAttribute(): string
    {
        $numberFormatter = NumberFormatter::create(App::currentLocale(), NumberFormatter::DEFAULT_STYLE);
        switch ($this->type) {
            case CouponTypeEnum::CART_PERCENT:
            case CouponTypeEnum::PRODUCT_PERCENT:
                return $this->value . '%';
            case CouponTypeEnum::CART_FIXED:
            case CouponTypeEnum::PRODUCT_FIXED:
                return $numberFormatter->formatCurrency($this->value, app(PaymentsService::class)->getPaymentsConfig()->getDefaultCurrency());
            default:
                return $numberFormatter->format($this->value);
        }
    }

    public function emails(): HasMany
    {
        return $this->hasMany(CouponEmail::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(CouponProduct::class);
    }

    public function includedProducts(): HasMany
    {
        return $this->products()->where('excluded', false);
    }

    public function excludedProducts(): HasMany
    {
        return $this->products()->where('excluded', true);
    }
}
