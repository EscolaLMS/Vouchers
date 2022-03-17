<?php

namespace EscolaLms\Vouchers\Http\Requests;

use EscolaLms\Vouchers\Enums\CouponTypeEnum;
use EscolaLms\Vouchers\Models\Category;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CreateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Coupon::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['string', 'nullable'],
            'code' => ['required', 'string'],
            'active' => ['boolean'],
            'type' => ['required', Rule::in(CouponTypeEnum::getValues())],
            'active_from' => ['datetime', 'nullable'],
            'active_to' => ['datetime', 'nullable'],
            'limit_usage' => ['integer', 'nullable'],
            'limit_per_user' => ['integer', 'nullable'],
            'min_cart_price' => ['integer', 'nullable'],
            'max_cart_price' => ['integer', 'nullable'],
            'amount' => ['required', 'integer'],
            'included_products' => ['array'],
            'included_products.*' => ['integer', Rule::exists(Product::class, 'id')],
            'excluded_products' => ['array'],
            'excluded_products.*' => ['integer', Rule::exists(Product::class, 'id')],
            'emails' => ['array'],
            'emails.*' => ['sometimes', 'string'],
            'included_categories' => ['array'],
            'included_categories.*' => ['integer', Rule::exists(Category::class, 'id')],
            'excluded_categories' => ['array'],
            'excluded_categories.*' => ['integer', Rule::exists(Category::class, 'id')],
        ];
    }
}
