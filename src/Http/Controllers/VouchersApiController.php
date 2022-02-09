<?php

namespace EscolaLms\Vouchers\Http\Controllers;

use EscolaLms\Courses\Http\Controllers\AppBaseController;
use EscolaLms\Vouchers\Exceptions\CouponInactiveException;
use EscolaLms\Vouchers\Exceptions\CouponNotApplicableException;
use EscolaLms\Vouchers\Http\Controllers\Swagger\VouchersApiControllerSwagger;
use EscolaLms\Vouchers\Http\Requests\ApplyCouponRequest;
use EscolaLms\Vouchers\Services\ShopService;
use Illuminate\Http\JsonResponse;

class VouchersApiController extends AppBaseController implements VouchersApiControllerSwagger
{
    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        try {
            ShopService::fromUserId($request->user())->setCoupon($request->getCoupon());
        } catch (CouponInactiveException $ex) {
            return $this->sendError($ex->getMessage(), 403);
        } catch (CouponNotApplicableException $ex) {
            return $this->sendError($ex->getMessage(), 400);
        }
        return $this->sendSuccess(__("Coupon added to cart"));
    }
}
