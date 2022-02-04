<?php

namespace EscolaLms\Vouchers\Tests\Api;

use EscolaLms\Cart\Models\Course;
use EscolaLms\Core\Enums\UserRole;
use EscolaLms\Courses\Events\CourseAccessStarted;
use EscolaLms\Courses\Events\CourseAssigned;
use EscolaLms\Payments\Models\Payment;
use EscolaLms\Payments\Tests\Traits\CreatesPaymentMethods;
use EscolaLms\Vouchers\Database\Seeders\VoucherPermissionsSeeder;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Models\User;
use EscolaLms\Vouchers\Services\ShopService;
use EscolaLms\Vouchers\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class UserVoucherTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesPaymentMethods;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(VoucherPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole(UserRole::STUDENT);
    }

    public function testApplyCartPercentVoucherAndPurchase()
    {
        Notification::fake();
        Event::fake([CourseAccessStarted::class, CourseAssigned::class]);

        $course = Course::factory()->create([
            'base_price' => 1000
        ]);
        $course2 = Course::factory()->create([
            'base_price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course->getKey());
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course2->getKey());
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('15.00', $cartDataApi['total']);
        $this->assertEquals('0.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertNull($cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $coupon = Coupon::factory()->cart_percent()->create();

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('13.50', $cartDataApi['total']);
        $this->assertEquals('1.50', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay', ['paymentMethodId' => $this->getPaymentMethodId()]);
        $this->response->assertOk();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 1350,
                        'subtotal' => 1500,
                        // 'discount' => 150,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(3)
            ->assertJsonCount(2, 'data.0.items');
        $user->refresh();
        $course->refresh();
        $this->assertTrue($course->alreadyBoughtBy($user));
        $this->assertTrue($user->courses()->where('courses.id', $course->getKey())->exists());

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(1350, $payment->amount);
    }

    public function testApplyCartFixedVoucherAndPurchase()
    {
        Notification::fake();
        Event::fake([CourseAccessStarted::class, CourseAssigned::class]);

        $course = Course::factory()->create([
            'base_price' => 1000
        ]);
        $course2 = Course::factory()->create([
            'base_price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course->getKey());
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course2->getKey());
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('15.00', $cartDataApi['total']);
        $this->assertEquals('0.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertNull($cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $coupon = Coupon::factory()->cart_fixed()->create();

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('5.00', $cartDataApi['total']);
        $this->assertEquals('10.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay', ['paymentMethodId' => $this->getPaymentMethodId()]);
        $this->response->assertOk();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 500,
                        'subtotal' => 1500,
                        //'discount' => 1000,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(3)
            ->assertJsonCount(2, 'data.0.items');
        $user->refresh();
        $course->refresh();
        $this->assertTrue($course->alreadyBoughtBy($user));
        $this->assertTrue($user->courses()->where('courses.id', $course->getKey())->exists());

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(500, $payment->amount);
    }

    public function testApplyProductFixedVoucherAndPurchase()
    {
        Notification::fake();
        Event::fake([CourseAccessStarted::class, CourseAssigned::class]);

        $course = Course::factory()->create([
            'base_price' => 1000
        ]);
        $course2 = Course::factory()->create([
            'base_price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course->getKey());
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course2->getKey());
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('15.00', $cartDataApi['total']);
        $this->assertEquals('0.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertNull($cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_fixed()->create();
        $coupon->products()->save(new CouponProduct([
            'product_id' => $course->getKey(),
            'product_type' => $course->getMorphClass(),
            'excluded' => false,
        ]));
        //dd($coupon->includedProducts->toArray());

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('5.00', $cartDataApi['total']);
        $this->assertEquals('10.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay', ['paymentMethodId' => $this->getPaymentMethodId()]);
        $this->response->assertOk();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 500,
                        'subtotal' => 1500,
                        //'discount' => 1000,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(3)
            ->assertJsonCount(2, 'data.0.items');
        $user->refresh();
        $course->refresh();
        $this->assertTrue($course->alreadyBoughtBy($user));
        $this->assertTrue($user->courses()->where('courses.id', $course->getKey())->exists());

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(500, $payment->amount);
    }

    public function testApplyProductPercentVoucherAndPurchase()
    {
        Notification::fake();
        Event::fake([CourseAccessStarted::class, CourseAssigned::class]);

        $course = Course::factory()->create([
            'base_price' => 1000
        ]);
        $course2 = Course::factory()->create([
            'base_price' => 500
        ]);

        $user = $this->user;

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course->getKey());
        $this->response->assertStatus(200);
        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/course/' . $course2->getKey());
        $this->response->assertStatus(200);

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('15.00', $cartDataApi['total']);
        $this->assertEquals('0.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertNull($cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        /** @var Coupon $coupon */
        $coupon = Coupon::factory()->product_percent()->create();
        $coupon->products()->save(new CouponProduct([
            'product_id' => $course->getKey(),
            'product_type' => $course->getMorphClass(),
            'excluded' => false,
        ]));
        //dd($coupon->includedProducts->toArray());

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/voucher', ['code' => $coupon->code]);
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/cart');
        $this->response->assertOk();

        $cartDataApi = $this->response->json()['data'];

        $this->assertEquals('14.00', $cartDataApi['total']);
        $this->assertEquals('1.00', $cartDataApi['discount']);
        $this->assertEquals('15.00', $cartDataApi['total_without_discount']);
        $this->assertEquals($coupon->code, $cartDataApi['coupon']);

        $shopService = ShopService::fromUserId($user);
        $cartDataService = $shopService->getCartData();

        $this->assertEquals($cartDataService, $cartDataApi);

        $this->response = $this->actingAs($user, 'api')->json('POST', '/api/cart/pay', ['paymentMethodId' => $this->getPaymentMethodId()]);
        $this->response->assertOk();
        $this->response = $this->actingAs($user, 'api')->json('GET', '/api/orders');
        $this->response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'status' => 'PAID',
                        'total' => 1400,
                        'subtotal' => 1500,
                        //'discount' => 100,
                        'tax' => 0
                    ]
                ]
            ])
            ->assertJsonCount(3)
            ->assertJsonCount(2, 'data.0.items');
        $user->refresh();
        $course->refresh();
        $this->assertTrue($course->alreadyBoughtBy($user));
        $this->assertTrue($user->courses()->where('courses.id', $course->getKey())->exists());

        $order_id = $this->response->json('data.0.id');
        $payment = Payment::where('payable_id', $order_id)->first();
        $this->assertEquals(1400, $payment->amount);
    }
}
