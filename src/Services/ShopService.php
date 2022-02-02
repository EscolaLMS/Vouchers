<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Cart\Services\ShopService as CartShopService;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\Order;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Services\Contracts\ShopWithCouponsServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use Exception;
use Illuminate\Support\Facades\Config;
use Treestoneit\ShoppingCart\Models\Cart as BaseCart;
use Treestoneit\ShoppingCart\Models\CartItem;

class ShopService extends CartShopService implements ShopWithCouponsServiceContract
{
    protected CouponsServiceContract $couponsService;

    /** @var Cart $cart */
    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->couponsService = app(CouponsServiceContract::class);
        if (!empty($cart) && !is_a($cart, Cart::class)) {
            $cart = Cart::find($cart->getKey());
        }
        parent::__construct($cart);
    }

    public function discount(?int $taxRate = null): int
    {
        return $this->getDiscountStrategy()->calculateDiscount($this->cart, $taxRate);
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

    public function total(?int $taxRate = null): int
    {
        return $this->totalWithoutDiscount($taxRate) - $this->discount($taxRate);
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function setCart(BaseCart $cart): void
    {
        if (!is_a($cart, Cart::class)) {
            $cart = Cart::find($cart->getKey());
        }
        parent::setCart($cart);
    }

    public function setCoupon(?Coupon $coupon): self
    {
        if (!is_null($coupon) && !$this->couponsService->couponCanBeUsedOnCart($coupon, $this->cart)) {
            if (!$this->couponsService->couponIsActive($coupon)) {
                throw new Exception(__('Coupon :code is no longer active', ['code' => $coupon->code]));
            }
            throw new Exception(__('Coupon :code can not be applied to this Cart', ['code' => $coupon->code]));
        }

        $this->cart->coupon = $coupon;
        $this->cart->save();
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

    public function getCartData(): array
    {
        return array_merge(parent::getCartData(), [
            'total_without_discount' =>  $this->moneyFormat((int) $this->totalWithoutDiscount()),
            'coupon' => $this->cart->coupon ? $this->cart->coupon->code : null,
        ]);
    }

    public function createOrder(): Order
    {
        $order = parent::createOrder();
        $order = Order::find($order->getKey());

        $order->coupon = $this->getCoupon();
        $order->save();

        return $order;
    }
}
