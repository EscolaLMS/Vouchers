<?php

namespace EscolaLms\Vouchers\Exceptions;

use Exception;

class CouponNotApplicableException extends Exception
{

    public function __construct(string $couponCode)
    {
        parent::__construct(__('Coupon :code can not be applied to this Cart', ['code' => $couponCode]));
    }
}
