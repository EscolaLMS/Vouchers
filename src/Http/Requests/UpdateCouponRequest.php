<?php

namespace EscolaLms\Vouchers\Http\Requests;

use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use EscolaLms\Vouchers\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->getCoupon());
    }

    public function rules(): array
    {
        return [
            'name' => ['string', 'nullable'],
            'code' => ['sometimes', 'required', 'string'],
            'active' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'required', Rule::in(CouponTypeEnum::getValues())],
            'active_from' => ['datetime', 'nullable'],
            'active_to' => ['datetime', 'nullable'],
            'limit_usage' => ['integer', 'nullable'],
            'limit_per_user' => ['integer', 'nullable'],
            'min_cart_price' => ['integer', 'nullable'],
            'max_cart_price' => ['integer', 'nullable'],
            'amount' => ['sometimes', 'required', 'integer'],
            'included_products' => ['sometimes', 'array'],
            'included_products.*' => ['integer'],
            'excluded_products' => ['sometimes', 'array'],
            'excluded_products.*' => ['integer'],
            'emails' => ['array'],
            'emails.*' => ['sometimes', 'string'],
        ];
    }

    public function getCoupon(): Coupon
    {
        return Coupon::findOrFail($this->route('id'));
    }
}
