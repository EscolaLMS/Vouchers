<?php

namespace EscolaLms\Vouchers\Strategies\Contracts;

use EscolaLms\Vouchers\Services\ShopService;

interface DiscountStrategyContract
{
    public function calculateDiscount(ShopService $shopService, ?int $taxRate = null): int;
}
