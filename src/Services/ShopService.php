<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Cart\Services\Concerns\AvoidDeleted;
use EscolaLms\Cart\Services\ShopService as CartShopService;
use EscolaLms\Vouchers\Exceptions\CouponInactiveException;
use EscolaLms\Vouchers\Exceptions\CouponNotApplicableException;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\Order;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Services\Contracts\ShopWithCouponsServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Treestoneit\ShoppingCart\Models\Cart as BaseCart;
use Treestoneit\ShoppingCart\Models\CartItem;

class ShopService extends CartShopService implements ShopWithCouponsServiceContract
{
    use AvoidDeleted;

    protected CouponsServiceContract $couponsService;

    /** @var Cart $cart */
    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->couponsService = app(CouponsServiceContract::class);
        parent::__construct($cart);
    }

    public static function fromUserId(Authenticatable $user): self
    {
        return new static(Cart::where('user_id', $user->getAuthIdentifier())->firstOrNew([
            'user_id' => $user->getAuthIdentifier(),
        ]));
    }

    public function total(?int $taxRate = null): int
    {
        return $this->totalWithoutDiscount($taxRate) - $this->discount($taxRate);
    }

    public function discount(?int $taxRate = null): int
    {
        return $this->getDiscountStrategy()->calculateDiscount($this->getCart(), $taxRate);
    }

    public function totalWithoutDiscount(?int $taxRate = null): int
    {
        return parent::total($taxRate);
    }

    public function taxForItem(CartItem $item, ?int $taxRate = null): int
    {
        return ($this->getTaxAmountForItem($taxRate))($item);
    }

    public function getDefaultTaxRate()
    {
        if (Config::get('shopping-cart.tax.mode') == 'flat') {
            return Config::get('shopping-cart.tax.rate');
        } else {
            return 0;
        }
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(BaseCart $cart): void
    {
        if (!is_a($cart, Cart::class)) {
            if ($cart->exists) {
                $cart = Cart::find($cart->getKey());
            } else {
                $cart = new Cart($cart->getAttributes());
            }
        }
        parent::setCart($cart);
    }

    public function setCoupon(?Coupon $coupon): self
    {
        if (!is_null($coupon) && !$this->couponsService->couponCanBeUsedOnCart($coupon, $this->cart)) {
            if (!$this->couponsService->couponIsActive($coupon)) {
                throw new CouponInactiveException($coupon->code);
            }
            throw new CouponNotApplicableException($coupon->code);
        }

        $this->cart->coupon_id = $coupon->getKey();
        $this->cart->save();
        $this->cart->refresh();
        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->cart->coupon;
    }

    private function getDiscountStrategy(): DiscountStrategyContract
    {
        return $this->couponsService->getDiscountStrategyForCoupon($this->cart->coupon);
    }

    public function getCartData(?int $taxRate = null): array
    {
        return array_merge(parent::getCartData(), [
            'discount' => $this->moneyFormat((int) $this->discount()),
            'total_without_discount' =>  $this->moneyFormat((int) $this->totalWithoutDiscount()),
            'coupon' => $this->cart->coupon ? $this->cart->coupon->code : null,
        ]);
    }

    protected function moneyFormat(int $value): string
    {
        $quotient = intdiv($value, 100);
        $remainder = $value - ($quotient * 100);

        $result = (string) $quotient . '.';
        if ($remainder < 10) {
            return $result . '0' . (string) $remainder;
        }
        return $result . (string) $remainder;
    }

    public function createOrder(): Order
    {
        $order = parent::createOrder();
        $order = Order::find($order->getKey());

        $coupon =  $this->getCoupon();
        if ($coupon) {
            $order->coupon_id = $coupon->getKey();
        }
        $order->discount = (int) $this->discount();
        $order->save();

        return $order;
    }
}
