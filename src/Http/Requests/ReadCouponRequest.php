<?php

namespace EscolaLms\Vouchers\Http\Requests;

use EscolaLms\Vouchers\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReadCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->getCoupon();

        return $coupon && Gate::allows('read', $coupon);
    }

    public function rules(): array
    {
        return [];
    }

    public function getCoupon(): Coupon
    {
        return Coupon::find($this->route('id'));
    }
}
