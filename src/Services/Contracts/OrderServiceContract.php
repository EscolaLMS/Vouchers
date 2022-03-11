<?php

namespace EscolaLms\Vouchers\Services\Contracts;

use EscolaLms\Cart\Dtos\ClientDetailsDto;
use EscolaLms\Cart\Services\Contracts\OrderServiceContract as BaseOrderServiceContract;
use EscolaLms\Cart\Models\Cart as BaseCart;
use EscolaLms\Vouchers\Models\Order;

interface OrderServiceContract extends BaseOrderServiceContract
{
    public function createOrderFromCart(BaseCart $cart, ?ClientDetailsDto $clientDetailsDto = null): Order;
}
