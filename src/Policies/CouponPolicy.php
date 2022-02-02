<?php

namespace EscolaLms\Vouchers\Policies;

use EscolaLms\Core\Models\User;
use EscolaLms\Vouchers\Enums\VoucherPermissionsEnum;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
{
    use HandlesAuthorization;

    private CouponsServiceContract $couponsService;

    public function __construct(CouponsServiceContract $couponsService)
    {
        $this->couponsService = $couponsService;
    }

    public function viewAny(User $user)
    {
        return $user->can(VoucherPermissionsEnum::COUPONS_LIST);
    }

    public function view(User $user, Coupon $course)
    {
        return $user->can(VoucherPermissionsEnum::COUPON_READ);
    }

    public function create(User $user)
    {
        return $user->can(VoucherPermissionsEnum::COUPON_CREATE);
    }

    public function update(User $user, Coupon $coupon)
    {
        return $user->can(VoucherPermissionsEnum::COUPON_UPDATE);
    }

    public function delete(User $user, Coupon $coupon)
    {
        return $user->can(VoucherPermissionsEnum::COUPON_DELETE) && $coupon->orders()->count() === 0;
    }

    public function apply(User $user, Coupon $coupon)
    {
        return $user->can(VoucherPermissionsEnum::COUPON_USE) && $this->couponsService->couponIsActive($coupon);
    }
}
