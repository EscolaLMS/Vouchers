<?php

namespace EscolaLms\Vouchers\Http\Controllers;

use EscolaLms\Core\Http\Controllers\EscolaLmsBaseController;
use EscolaLms\Vouchers\Exceptions\CouponInactiveException;
use EscolaLms\Vouchers\Exceptions\CouponNotApplicableException;
use EscolaLms\Vouchers\Http\Controllers\Swagger\VouchersApiControllerSwagger;
use EscolaLms\Vouchers\Http\Requests\ApplyCouponRequest;
use EscolaLms\Vouchers\Services\Contracts\ShopServiceContract;
use Illuminate\Http\JsonResponse;

class VouchersApiController extends EscolaLmsBaseController implements VouchersApiControllerSwagger
{
    protected ShopServiceContract $shopService;

    public function __construct(ShopServiceContract $shopService)
    {
        $this->shopService = $shopService;
    }

    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        try {
            $cart = $this->shopService->cartForUser($request->user());
            $cartManager = $cart->getCartManager();
            $cartManager->setCoupon($request->getCoupon());
        } catch (CouponInactiveException $ex) {
            return $this->sendError($ex->getMessage(), 403);
        } catch (CouponNotApplicableException $ex) {
            return $this->sendError($ex->getMessage(), 400);
        }
        return $this->sendSuccess(__("Coupon added to cart"));
    }
}
