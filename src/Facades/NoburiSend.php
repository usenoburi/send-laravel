<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Noburi\Send\Client;

/**
 * @method static \Noburi\Send\Service\Emails emails()
 * @method static \Noburi\Send\Service\Domains domains()
 * @method static \Noburi\Send\Service\Webhooks webhooks()
 * @method static \Noburi\Send\Service\Templates templates()
 * @method static string baseUrl()
 *
 * @see Client
 */
final class NoburiSend extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'noburi-send';
    }
}
