<?php

namespace EscolaLms\Vouchers\Http\Controllers;

use EscolaLms\Courses\Http\Controllers\AppBaseController;
use EscolaLms\Vouchers\Http\Requests\CreateCouponRequest;
use EscolaLms\Vouchers\Http\Requests\DeleteCouponRequest;
use EscolaLms\Vouchers\Http\Requests\ListCouponsRequest;
use EscolaLms\Vouchers\Http\Requests\ReadCouponRequest;
use EscolaLms\Vouchers\Http\Requests\UpdateCouponRequest;
use EscolaLms\Vouchers\Http\Resources\CouponResource;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Services\Contracts\ShopWithCouponsServiceContract;
use Illuminate\Http\Response;

class VouchersAdminApiController extends AppBaseController
{
    private ShopWithCouponsServiceContract $shopService;
    private CouponsServiceContract $couponsService;

    public function __construct(ShopWithCouponsServiceContract $shopService, CouponsServiceContract $couponsService)
    {
        $this->shopService = $shopService;
        $this->couponsService = $couponsService;
    }

    public function index(ListCouponsRequest $request): Response
    {

        return $this->sendResponseForResource(CouponResource::collection($coupon));
    }

    public function create(CreateCouponRequest $request): Response
    {
        return $this->sendResponseForResource(CouponResource::make($coupon));
    }

    public function read(ReadCouponRequest $request): Response
    {
        return $this->sendResponseForResource(CouponResource::make($coupon));
    }

    public function update(UpdateCouponRequest $request): Response
    {
        return $this->sendResponseForResource(CouponResource::make($coupon));
    }

    public function delete(DeleteCouponRequest $request): Response
    {
        return $this->sendSuccess(__('Coupon was deleted'));
    }
}
