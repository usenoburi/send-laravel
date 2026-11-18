<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Tests;

use Noburi\Send\Laravel\NoburiSendServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

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
        return [NoburiSendServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('noburi-send.api_key', 'ps_test_key');
        $app['config']->set('noburi-send.routes', self::$routes);
        $app['config']->set('noburi-send.webhook.secret', self::$webhookSecret);
        $app['config']->set('mail.default', 'noburi-send');
        $app['config']->set('mail.mailers.noburi-send', ['transport' => 'noburi-send']);
        $app['config']->set('mail.from', ['address' => 'hello@acme.com', 'name' => 'Acme']);
    }
}
