<?php

namespace EscolaLms\Vouchers\Services\Contracts;

use EscolaLms\Cart\Services\Contracts\ShopServiceContract;

interface ShopWithCouponsServiceContract extends ShopServiceContract
{
    public function discount(?int $taxRate = null): int;
    public function totalWithoutDiscount(?int $taxRate = null): int;
}
