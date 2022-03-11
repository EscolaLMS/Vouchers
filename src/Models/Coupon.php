<?php

namespace EscolaLms\Vouchers\Models;

use EscolaLms\Vouchers\Database\Factories\CouponFactory;
use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use EscolaLms\Vouchers\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use NumberFormatter;

/**
 * EscolaLms\Vouchers\Models\Coupon
 *
 * @OA\Schema (
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
 * 
 * @property int $id
 * @property string|null $name
 * @property string $code
 * @property bool $active
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $active_from
 * @property \Illuminate\Support\Carbon|null $active_to
 * @property int|null $limit_usage
 * @property int|null $limit_per_user
 * @property int|null $min_cart_price
 * @property int|null $max_cart_price
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\EscolaLms\Vouchers\Models\Cart[] $carts
 * @property-read int|null $carts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\EscolaLms\Vouchers\Models\CouponEmail[] $emails
 * @property-read int|null $emails_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $excludedProducts
 * @property-read int|null $excluded_products_count
 * @property-read string $value_string
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $includedProducts
 * @property-read int|null $included_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\EscolaLms\Vouchers\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $products
 * @property-read int|null $products_count
 * @method static \EscolaLms\Vouchers\Database\Factories\CouponFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereActiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereActiveTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereLimitPerUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereLimitUsage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereMaxCartPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereMinCartPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Coupon extends Model
{
    use HasFactory;

    public $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
        'active_from' => 'datetime',
        'active_to' => 'datetime',
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupons_products', 'coupon_id', 'product_id')->using(CouponProduct::class);
    }

    public function includedProducts(): BelongsToMany
    {
        return $this->products()->wherePivot('excluded', false);
    }

    public function excludedProducts(): BelongsToMany
    {
        return $this->products()->wherePivot('excluded', true);
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
