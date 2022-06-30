<?php

namespace EscolaLms\Vouchers\Http\Requests;

use EscolaLms\Vouchers\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UnapplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('unapply', Coupon::class);
    }

    public function rules(): array
    {
        return [];
    }
}
