<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PulseSend\Laravel\PulseSendServiceProvider;

abstract class TestCase extends Orchestra
{
    /** Read at boot: change them, then call refreshApplication(). */
    public static mixed $routes = true;

    public static ?string $webhookSecret = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        self::$routes = true;
        self::$webhookSecret = null;
    }

    protected function getPackageProviders($app): array
    {
        return [PulseSendServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pulsesend.api_key', 'ps_test_key');
        $app['config']->set('pulsesend.routes', self::$routes);
        $app['config']->set('pulsesend.webhook.secret', self::$webhookSecret);
        $app['config']->set('mail.default', 'pulsesend');
        $app['config']->set('mail.mailers.pulsesend', ['transport' => 'pulsesend']);
        $app['config']->set('mail.from', ['address' => 'hello@acme.com', 'name' => 'Acme']);
    }
}
