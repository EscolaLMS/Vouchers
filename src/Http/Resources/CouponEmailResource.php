<?php

namespace EscolaLms\Vouchers\Http\Resources;

use EscolaLms\Vouchers\Models\CouponEmail;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponEmailResource extends JsonResource
{
    public function __construct(CouponEmail $couponEmail)
    {
        parent::__construct($couponEmail);
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'email' => $this->email
        ];
    }
}
