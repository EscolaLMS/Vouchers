<?php

namespace EscolaLms\Vouchers\Http\Resources;

use EscolaLms\Cart\Http\Resources\CartResource as BaseCartResource;
use EscolaLms\Cart\Models\Cart as BaseCart;
use EscolaLms\Vouchers\Models\Cart;

class CartResource extends BaseCartResource
{
    public function __construct(BaseCart $cart, ?int $taxRate = null)
    {
        $cart = $cart instanceof Cart ? $cart : Cart::find($cart->getKey());
        parent::__construct($cart, $taxRate);
    }

    protected function getCart(): Cart
    {
        return $this->resource;
    }

    public function toArray($request)
    {
        return self::apply([
            'total' => $this->getCart()->total,
            'subtotal' =>  $this->getCart()->subtotal,
            'tax' =>  $this->getCart()->getTaxAttribute($this->taxRate),
            'items' => CartItemResource::collection($this->getCart()->items),
            'additional_discount' => $this->getCart()->getAdditionalDiscountAttribute(),
            'total_prediscount' => $this->getCart()->getTotalPreDiscountAttribute(),
            'coupon' => optional($this->getCart()->coupon)->code,
        ], $this);
    }
}
