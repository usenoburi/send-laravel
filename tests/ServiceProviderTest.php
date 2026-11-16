<?php

declare(strict_types=1);

use PulseSend\Client;
use PulseSend\Laravel\Exceptions\ApiKeyIsMissing;
use PulseSend\Laravel\Facades\PulseSend;

it('binds one client from the config', function () {
    config(['pulsesend.base_url' => 'https://pulsesend.test']);

    expect(app('pulsesend'))->toBeInstanceOf(Client::class)
        ->and(app(Client::class))->toBe(app('pulsesend'))
        ->and(PulseSend::baseUrl())->toBe('https://pulsesend.test');
});

it('falls back on services.pulsesend.key', function () {
    config(['pulsesend.api_key' => null, 'services.pulsesend.key' => 'ps_test_other']);
    app()->forgetInstance(Client::class);

    expect(app(Client::class))->toBeInstanceOf(Client::class);
});

it('says when the key is missing', function () {
    config(['pulsesend.api_key' => null]);
    app()->forgetInstance(Client::class);

    expect(fn () => app(Client::class))->toThrow(ApiKeyIsMissing::class, 'PULSESEND_API_KEY');
});

it('reaches the API through the facade', function () {
    $history = fakePulseSend([jsonResponse(202, ['id' => 'em_9'])]);
    PulseSend::clearResolvedInstances();

    $email = PulseSend::emails()->send(['from' => 'hello@acme.com', 'to' => ['a@b.com'], 'subject' => 'Hi', 'template' => 'welcome', 'scheduled_at' => 'in 1 hour']);

    expect($email['id'])->toBe('em_9')
        ->and(sentBody($history)['template'])->toBe('welcome');
});
