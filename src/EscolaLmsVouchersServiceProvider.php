<?php

namespace EscolaLms\Vouchers;

use EscolaLms\Cart\Services\Contracts\ShopServiceContract;
use EscolaLms\Vouchers\Services\ShopService;
use Illuminate\Support\ServiceProvider;
use EscolaLms\Cart\Services\ShopService as CartShopService;

/**
 * SWAGGER_VERSION
 */
class EscolaLmsVouchersServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'escolalms_vouchers';

    public $singletons = [];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', self::CONFIG_KEY);

        $this->app->extend(ShopServiceContract::class, function ($service, $app) {
            /** @var CartShopService $service */
            return new ShopService($service->getModel());
        });
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
