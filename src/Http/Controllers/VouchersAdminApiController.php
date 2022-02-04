<?php

namespace EscolaLms\Vouchers\Http\Controllers;

use EscolaLms\Courses\Http\Controllers\AppBaseController;
use EscolaLms\Vouchers\Http\Requests\CreateCouponRequest;
use EscolaLms\Vouchers\Http\Requests\DeleteCouponRequest;
use EscolaLms\Vouchers\Http\Requests\ListCouponsRequest;
use EscolaLms\Vouchers\Http\Requests\ReadCouponRequest;
use EscolaLms\Vouchers\Http\Requests\UpdateCouponRequest;
use EscolaLms\Vouchers\Http\Resources\CouponResource;
use EscolaLms\Vouchers\Repositories\Contracts\CouponsRepositoryContract;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use Illuminate\Http\JsonResponse;

class VouchersAdminApiController extends AppBaseController
{
    private CouponsRepositoryContract $couponsRepository;
    private CouponsServiceContract $couponsService;

    public function __construct(CouponsRepositoryContract $couponsRepository, CouponsServiceContract $couponsService)
    {
        $this->couponsRepository = $couponsRepository;
        $this->couponsService = $couponsService;
    }

    public function index(ListCouponsRequest $request): JsonResponse
    {
        return $this->sendResponseForResource(CouponResource::collection($this->couponsRepository->allQuery()->paginate()));
    }

    public function create(CreateCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponsService->createCoupon($request->validated());
        return $this->sendResponseForResource(CouponResource::make($coupon));
    }

    public function read(ReadCouponRequest $request): JsonResponse
    {
        return $this->sendResponseForResource(CouponResource::make($request->getCoupon()));
    }

    public function update(UpdateCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponsService->updateCoupon($request->getCoupon(), $request->validated());
        return $this->sendResponseForResource(CouponResource::make($coupon));
    }

    public function delete(DeleteCouponRequest $request): JsonResponse
    {
        $request->getCoupon()->delete();
        return $this->sendSuccess(__('Coupon was deleted'));
    }
}
