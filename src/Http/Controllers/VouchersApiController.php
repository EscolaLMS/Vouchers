<?php

namespace EscolaLms\Vouchers\Http\Controllers;

use EscolaLms\Courses\Http\Controllers\AppBaseController;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Services\Contracts\ShopWithCouponsServiceContract;

class VouchersApiController extends AppBaseController
{
    private ShopWithCouponsServiceContract $shopService;
    private CouponsServiceContract $couponsService;

    public function __construct(ShopWithCouponsServiceContract $shopService, CouponsServiceContract $couponsService)
    {
        $this->shopService = $shopService;
        $this->couponsService = $couponsService;
    }

    public function apply()
    {
    }
}
