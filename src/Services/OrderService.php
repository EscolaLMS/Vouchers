<?php

namespace EscolaLms\Vouchers\Services;

use EscolaLms\Cart\Dtos\ClientDetailsDto;
use EscolaLms\Cart\Enums\OrderStatus;
use EscolaLms\Cart\Events\OrderCreated;
use EscolaLms\Cart\Models\Cart as BaseCart;
use EscolaLms\Cart\Services\OrderService as ServicesOrderService;
use EscolaLms\Vouchers\Models\Order;
use EscolaLms\Vouchers\Models\User;
use EscolaLms\Vouchers\Services\CartManager;
use EscolaLms\Vouchers\Services\Contracts\OrderServiceContract;
use Illuminate\Database\Eloquent\Model;

class OrderService extends ServicesOrderService implements OrderServiceContract
{
    /** @return Order */
    public function find($id): Model
    {
        return Order::findOrFail($id);
    }

    //TODO: change this method in Cart package to be easier to extend
    public function createOrderFromCart(BaseCart $cart, ?ClientDetailsDto $clientDetailsDto = null): Order
    {
        $optionalClientDetailsDto = optional($clientDetailsDto);

        /** @var User $user */
        $user = User::find($cart->user_id);

        $user->orders()->where('status', OrderStatus::PROCESSING)->update(['status' => OrderStatus::CANCELLED]);

        $cartManager = new CartManager($cart);

        $order = new Order($cart->getAttributes());
        $order->total = $cartManager->totalWithTax();
        $order->subtotal = $cartManager->total();
        $order->tax = $cartManager->taxInt();
        $order->status = OrderStatus::PROCESSING;
        $order->client_name = $optionalClientDetailsDto->getName() ?? $order->user->name;
        $order->client_street = $optionalClientDetailsDto->getStreet();
        $order->client_postal = $optionalClientDetailsDto->getPostal();
        $order->client_city = $optionalClientDetailsDto->getCity();
        $order->client_country = $optionalClientDetailsDto->getCountry();
        $order->client_company = $optionalClientDetailsDto->getCompany();
        $order->client_taxid = $optionalClientDetailsDto->getTaxid();
        $order->coupon_id = optional($cartManager->getCoupon())->getKey();
        $order->discount = $cartManager->additionalDiscount();
        $order->save();

        foreach ($cart->items as $item) {
            $this->storeCartItemAsOrderItem($order, $item);
        }

        event(new OrderCreated($order));

        return $order;
    }
}
