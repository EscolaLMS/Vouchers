<?php

namespace EscolaLms\Vouchers\Strategies;

use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Strategies\Contracts\DiscountStrategyContract;

class NoneDiscountStrategy implements DiscountStrategyContract
{
    public function calculateDiscount(ShopService $shopService, ?int $taxRate = null): int
    {
        return 0;
    }
}
