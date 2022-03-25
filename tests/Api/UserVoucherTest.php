<?php

namespace EscolaLms\Vouchers\Tests\Api;

use EscolaLms\Cart\Database\Seeders\CartPermissionSeeder;
use EscolaLms\Cart\Models\Product;
use EscolaLms\Core\Enums\UserRole;
use EscolaLms\Payments\Facades\PaymentGateway;
use EscolaLms\Payments\Models\Payment;
use EscolaLms\Vouchers\Database\Seeders\VoucherPermissionsSeeder;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Models\User;
use EscolaLms\Vouchers\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class UserVoucherTest extends TestCase
{
    use DatabaseTransactions;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(CartPermissionSeeder::class);
        $this->seed(VoucherPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole(UserRole::STUDENT);
    }

    public function testApplyCartPercentVoucherAndPurchase()
    {
        PaymentGateway::fake();
        Notification::fake();

        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => 1000,
            'tax_rate' => 0,
        ]);
        $product2 = Product::factory()->create([
            'price' => 500,
            'tax_rate' => 0,
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product2->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1500', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1500', $cartDataApi['total_prediscount']);
        $this->assertNull($cartDataApi['coupon']);

        $coupon = Coupon::factory()->cart_percent()->create();

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1350', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1350', $cartDataApi['total_prediscount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay');
        $this->response->assertCreated();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 1350,
                        'subtotal' => 1350,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(4)
            ->assertJsonCount(2, 'data.0.items');

        $user->refresh();
        $product->refresh();
        $this->assertTrue($product->getOwnedByUserAttribute($user));

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(1350, $payment->amount);
    }

    public function testApplyCartFixedVoucherAndPurchase()
    {
        PaymentGateway::fake();
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 1000
        ]);
        $product2 = Product::factory()->create([
            'price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product2->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1500', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1500', $cartDataApi['total_prediscount']);
        $this->assertNull($cartDataApi['coupon']);

        $coupon = Coupon::factory()->cart_fixed()->create();

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('500', $cartDataApi['total']);
        $this->assertEquals('1000', $cartDataApi['additional_discount']);
        $this->assertEquals('1500', $cartDataApi['total_prediscount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay');
        $this->response->assertCreated();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 500,
                        'subtotal' => 500,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(4)
            ->assertJsonCount(2, 'data.0.items');

        $user->refresh();
        $product->refresh();
        $this->assertTrue($product->getOwnedByUserAttribute($user));

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(500, $payment->amount);
    }

    public function testApplyProductFixedVoucherAndPurchase()
    {
        PaymentGateway::fake();
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 1000
        ]);
        $product2 = Product::factory()->create([
            'price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product2->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1500', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1500', $cartDataApi['total_prediscount']);
        $this->assertNull($cartDataApi['coupon']);

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_fixed()->create();
        CouponProduct::create([
            'coupon_id' => $coupon->getKey(),
            'product_id' => $product->getKey(),
            'excluded' => false,
        ]);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('500', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('500', $cartDataApi['total_prediscount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay');
        $this->response->assertCreated();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 500,
                        'subtotal' => 500,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(4)
            ->assertJsonCount(2, 'data.0.items');

        $user->refresh();
        $product->refresh();
        $this->assertTrue($product->getOwnedByUserAttribute($user));

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(500, $payment->amount);
    }

    public function testApplyProductPercentVoucherAndPurchase()
    {
        PaymentGateway::fake();
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 1000
        ]);
        $product2 = Product::factory()->create([
            'price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product2->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1500', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1500', $cartDataApi['total_prediscount']);
        $this->assertNull($cartDataApi['coupon']);

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_percent()->create();
        CouponProduct::create([
            'coupon_id' => $coupon->getKey(),
            'product_id' => $product->getKey(),
            'excluded' => false,
        ]);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('1400', $cartDataApi['total']);
        $this->assertEquals('0', $cartDataApi['additional_discount']);
        $this->assertEquals('1400', $cartDataApi['total_prediscount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay');
        $this->response->assertCreated();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 1400,
                        'subtotal' => 1400,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(4)
            ->assertJsonCount(2, 'data.0.items');

        $user->refresh();
        $product->refresh();
        $this->assertTrue($product->getOwnedByUserAttribute($user));

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(1400, $payment->amount);
    }

    public function testApplyInactiveCoupon()
    {
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 1000
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_percent()->create(['active_to' => Carbon::now()->subDay(),]);
        CouponProduct::create([
            'coupon_id' => $coupon->getKey(),
            'product_id' => $product->getKey(),
            'excluded' => false,
        ]);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertStatus(400);
        $this->response->assertJsonFragment([
            'message' => __('Coupon :code is no longer active', ['code' => $coupon->code])
        ]);
    }

    public function testApplyWrongCoupon()
    {
        Notification::fake();

        $product = Product::factory()->create([
            'price' => 1000
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/products/', [
            'id' => $product->getKey(),
        ]);
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_percent()->create();
        CouponProduct::create([
            'coupon_id' => $coupon->getKey(),
            'product_id' => $product->getKey(),
            'excluded' => true,
        ]);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertStatus(400);
        $this->response->assertJsonFragment([
            'message' => __('Coupon :code can not be applied to this Cart', ['code' => $coupon->code])
        ]);
    }
}
