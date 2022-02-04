<?php

namespace EscolaLms\Vouchers\Http\Resources;

use EscolaLms\Vouchers\Models\CouponProduct;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponProductResource extends JsonResource
{
    public function __construct(CouponProduct $couponProduct)
    {
        parent::__construct($couponProduct);
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'product_id' => $this->product_id,
            'product_type' => $this->product_id,
        ];
    }
}
