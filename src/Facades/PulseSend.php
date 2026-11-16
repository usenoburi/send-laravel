<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PulseSend\Client;

/**
 * @method static \PulseSend\Service\Emails emails()
 * @method static \PulseSend\Service\Domains domains()
 * @method static \PulseSend\Service\Webhooks webhooks()
 * @method static \PulseSend\Service\Templates templates()
 * @method static string baseUrl()
 *
 * @see Client
 */
final class PulseSend extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pulsesend';
    }
}
