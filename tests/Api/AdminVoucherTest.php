<?php

namespace EscolaLms\Vouchers\Tests\Api;

use EscolaLms\Cart\Models\Product;
use EscolaLms\Core\Enums\UserRole;
use EscolaLms\Vouchers\Database\Seeders\VoucherPermissionsSeeder;
use EscolaLms\Vouchers\Http\Resources\CouponResource;
use EscolaLms\Vouchers\Models\Coupon;
use EscolaLms\Vouchers\Models\CouponEmail;
use EscolaLms\Vouchers\Models\CouponProduct;
use EscolaLms\Vouchers\Models\User;
use EscolaLms\Vouchers\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminVoucherTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(VoucherPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole(UserRole::ADMIN);
    }

    public function testCreateCoupon()
    {
        $coupon = Coupon::factory()->make();
        $data = $coupon->toArray();

        $this->response = $this->actingAs($this->user, 'api')->json('POST', '/api/admin/vouchers/', $data);
        $this->response->assertCreated();

        $id = $this->response->json('data.id');
        $couponDb = Coupon::find($id);

        $this->response->assertJsonFragment([
            'data' => CouponResource::make($couponDb)->toArray(null)
        ]);
    }

    public function testCreateCouponWithProductsAndEmails()
    {
        $coupon = Coupon::factory()->make();
        $data = $coupon->toArray();

        $product = Product::factory()->create();
        $product2 = Product::factory()->create();

        $data['excluded_products'] = [
            $product->getKey(),
        ];
        $data['included_products'] = [
            $product2->getKey(),
        ];
        $data['emails'] = [
            $this->user->email
        ];

        $this->response = $this->actingAs($this->user, 'api')->json('POST', '/api/admin/vouchers/', $data);
        $this->response->assertCreated();

        $id = $this->response->json('data.id');
        /** @var Coupon $couponDb */
        $couponDb = Coupon::find($id);

        $this->response->assertJsonFragment([
            'data' => json_decode(CouponResource::make($couponDb)->toJson(), true)
        ]);

        $this->assertTrue($couponDb->excludedProducts->contains(fn (Product $eProduct) => $eProduct->getKey() === $product->getKey()));
        $this->assertTrue($couponDb->includedProducts->contains(fn (Product $iProduct) => $iProduct->getKey() === $product2->getKey()));
        $this->assertTrue($couponDb->emails->contains(fn (CouponEmail $email) => $email->email === $this->user->email));
    }

    public function testUpdateCoupon()
    {
        $coupon = Coupon::factory()->create([
            'name' => 'first',
        ]);
        $coupon2 = Coupon::factory()->make([
            'name' => 'second',
            'code' => 'SOMECODE'
        ]);

        $url =  '/api/admin/vouchers/' . $coupon->getKey();
        $this->response = $this->actingAs($this->user, 'api')->json('PATCH', $url, $coupon2->toArray());
        $this->response->assertOk();

        $coupon->refresh();
        $this->assertEquals('second', $coupon->name);
        $this->assertEquals('SOMECODE', $coupon->code);
    }

    public function testListCoupons()
    {
        $coupon = Coupon::factory()->create([
            'name' => 'first',
        ]);
        $coupon2 = Coupon::factory()->create([
            'name' => 'second',
        ]);

        $this->response = $this->actingAs($this->user, 'api')->json('GET', '/api/admin/vouchers');
        $this->response->assertOk();

        $this->response->assertJsonCount(2, 'data');
        $this->response->assertJsonFragment([
            'data' => CouponResource::collection([$coupon, $coupon2])->toArray(null)
        ]);
    }

    public function testReadCoupons()
    {
        $coupon = Coupon::factory()->create([
            'name' => 'first',
        ]);

        $this->response = $this->actingAs($this->user, 'api')->json('GET', '/api/admin/vouchers/' . $coupon->getKey());
        $this->response->assertOk();

        $this->response->assertJsonFragment([
            'data' => CouponResource::make($coupon)->toArray(null)
        ]);
    }

    public function testDeleteCoupon()
    {
        $coupon = Coupon::factory()->create([
            'name' => 'first',
        ]);
        $coupon2 = Coupon::factory()->create([
            'name' => 'second',
        ]);

        $this->response = $this->actingAs($this->user, 'api')->json('GET', '/api/admin/vouchers');
        $this->response->assertOk();

        $this->response->assertJsonCount(2, 'data');
        $this->response->assertJsonFragment([
            'data' => CouponResource::collection([$coupon, $coupon2])->toArray(null)
        ]);

        $this->response = $this->actingAs($this->user, 'api')->json('DELETE', '/api/admin/vouchers/' . $coupon2->getKey());
        $this->response->assertOk();

        $this->response = $this->actingAs($this->user, 'api')->json('GET', '/api/admin/vouchers');
        $this->response->assertOk();

        $this->response->assertJsonCount(1, 'data');
        $this->response->assertJsonFragment([
            'data' => CouponResource::collection([$coupon])->toArray(null)
        ]);
    }
}
