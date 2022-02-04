<?php

namespace EscolaLms\Vouchers;

use EscolaLms\Cart\Services\Contracts\ShopServiceContract;
use EscolaLms\Cart\Services\ShopService as CartShopService;
use EscolaLms\Vouchers\Models\Cart;
use EscolaLms\Vouchers\Providers\AuthServiceProvider;
use EscolaLms\Vouchers\Repositories\Contracts\CouponsRepositoryContract;
use EscolaLms\Vouchers\Repositories\CouponsRepository;
use EscolaLms\Vouchers\Services\Contracts\CouponsServiceContract;
use EscolaLms\Vouchers\Services\Contracts\ShopWithCouponsServiceContract;
use EscolaLms\Vouchers\Services\CouponsService;
use EscolaLms\Vouchers\Services\ShopService;
use Illuminate\Support\ServiceProvider;

/**
 * SWAGGER_VERSION
 */
class EscolaLmsVouchersServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'escolalms_vouchers';

    public $singletons = [
        CouponsServiceContract::class => CouponsService::class,
        CouponsRepositoryContract::class => CouponsRepository::class,
    ];

    public $bindings = [
        ShopWithCouponsServiceContract::class => ShopService::class,
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', self::CONFIG_KEY);

        $this->app->extend(ShopServiceContract::class, function ($service, $app) {
            /** @var CartShopService $service */
            $cart = $service->getModel();
            if ($cart->exists) {
                $cart = Cart::find($cart->getKey());
            } else {
                $cart = new Cart($cart->getAttributes());
            }
            return new ShopService($cart);
        });

        $this->app->register(AuthServiceProvider::class);
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (class_exists(\EscolaLms\Settings\Facades\AdministrableConfig::class)) {
        }
    }

    public function bootForConsole()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/config.php' => config_path(self::CONFIG_KEY . '.php'),
        ], self::CONFIG_KEY . '.config');
    }
}
