<?php

namespace EscolaLms\Vouchers\Exceptions;

use Exception;

class CouponInactiveException extends Exception
{

    public function __construct(string $couponCode)
    {
        parent::__construct(__('Coupon :code is no longer active', ['code' => $couponCode]));
    }
}
