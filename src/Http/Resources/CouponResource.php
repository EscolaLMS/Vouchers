<?php

namespace EscolaLms\Vouchers\Http\Resources;

use EscolaLms\Vouchers\Models\Coupon;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function __construct(Coupon $coupon)
    {
        parent::__construct($coupon);
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'active_from' => $this->active_from,
            'active_to' =>  $this->active_to,
            'limit_usage' =>  $this->limit_usage,
            'limit_per_user' =>  $this->limit_per_user,
            'min_cart_price' => $this->min_cart_price,
            'max_cart_price' =>  $this->max_cart_price,
            'amount' =>  $this->amount,
            'included_products' => $this->includedProducts,
            'excluded_products' => $this->excludedProducts,
            'emails' => $this->emails,
        ];
    }
}
