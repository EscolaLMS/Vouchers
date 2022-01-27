<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Cart\Services\Contracts\ShopServiceContract;
use EscolaLms\Cart\Services\ShopService as CartShopService;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;
use EscolaLms\Vouchers\Strategies\NoneDiscountStrategy;
use Illuminate\Support\Str;

class ShopService extends CartShopService implements ShopServiceContract, CouponsServiceContract
{
    protected ?Coupon $coupon;

    public function discount(?int $taxRate = null): int
    {
        return $this->getDiscountStrategy()->calculateDiscount($this, $taxRate);
    }

    public function total(?int $taxRate = null): int
    {
        return parent::total($taxRate) - $this->discount($taxRate);
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->$coupon = $coupon;
        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    private function getDiscountStrategy(): DiscountStrategyContract
    {
        if (is_null($this->coupon)) {
            return new NoneDiscountStrategy;
        }

        $className = 'EscolaLms\\Vouchers\\Strategies\\' . Str::studly($this->coupon->type) . 'DiscountStrategy';

        if (!class_exists($className)) {
            throw new \RuntimeException($className . ' strategy does not exist.');
        }

        return new $className($this->discount);
    }
}
