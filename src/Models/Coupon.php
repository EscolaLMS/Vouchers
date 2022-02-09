<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Vouchers\Database\Factories\CouponFactory;
use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NumberFormatter;


/**
 * @OA\Schema(
 *      schema="Coupon",
 *      required={"code", "type", "amount"},
 *      @OA\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="name",
 *          description="name",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="code",
 *          description="code",
 *          type="string"
 *      ), 
 *      @OA\Property(
 *          property="type",
 *          description="type",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="active_from",
 *          description="active_from",
 *          type="data",
 *      ),
 *      @OA\Property(
 *          property="active_to",
 *          description="active_to",
 *          type="data",
 *      ),
 *      @OA\Property(
 *          property="limit_usage",
 *          description="limit_usage",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="limit_per_user",
 *          description="limit_per_user",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="min_cart_price",
 *          description="min_cart_price",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="max_cart_price",
 *          description="max_cart_price",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="amount",
 *          description="amount",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="emails",
 *          description="array",
 *          @OA\Items(type="string")
 *      ),
 * )
 */
class Coupon extends Model
{
    use HasFactory;

    public $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
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

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected static function newFactory()
    {
        return new CouponFactory();
    }

    protected static function booted()
    {
        parent::booted();
        self::saving(function (Coupon $coupon) {
            $coupon->code = Str::upper($coupon->code);
        });
    }
}
