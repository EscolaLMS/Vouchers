<?php

namespace EscolaLms\TemplatesEmail\Tests;

use EscolaLms\Cart\CartServiceProvider;
use EscolaLms\Core\Models\User;
use EscolaLms\Core\Tests\TestCase as CoreTestCase;
use EscolaLms\Vouchers\EscolaLmsVouchersServiceProvider;
use Laravel\Passport\PassportServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

class TestCase extends CoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            ...parent::getPackageProviders($app),
            PermissionServiceProvider::class,
            PassportServiceProvider::class,
            CartServiceProvider::class,
            EscolaLmsVouchersServiceProvider::class,
        ];
        return $providers;
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('passport.client_uuids', true);
    }
}
