<?php

namespace EscolaLms\Vouchers\Http\Requests;

use EscolaLms\Vouchers\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->getCoupon();

        return $coupon && Gate::allows('delete', $coupon);
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
