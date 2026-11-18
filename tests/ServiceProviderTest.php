<?php

declare(strict_types=1);

use Noburi\Send\Client;
use Noburi\Send\Laravel\Exceptions\ApiKeyIsMissing;
use Noburi\Send\Laravel\Facades\NoburiSend;

it('binds one client from the config', function () {
    config(['noburi-send.base_url' => 'https://pulsesend.test']);

    expect(app('noburi-send'))->toBeInstanceOf(Client::class)
        ->and(app(Client::class))->toBe(app('noburi-send'))
        ->and(NoburiSend::baseUrl())->toBe('https://pulsesend.test');
});

it('falls back on services.noburi-send.key', function () {
    config(['noburi-send.api_key' => null, 'services.noburi-send.key' => 'ps_test_other']);
    app()->forgetInstance(Client::class);

    expect(app(Client::class))->toBeInstanceOf(Client::class);
});

it('says when the key is missing', function () {
    config(['noburi-send.api_key' => null]);
    app()->forgetInstance(Client::class);

    expect(fn () => app(Client::class))->toThrow(ApiKeyIsMissing::class, 'NOBURI_SEND_API_KEY');
});

it('reaches the API through the facade', function () {
    $history = fakeNoburiSend([jsonResponse(202, ['id' => 'em_9'])]);
    NoburiSend::clearResolvedInstances();

    $email = NoburiSend::emails()->send(['from' => 'hello@acme.com', 'to' => ['a@b.com'], 'subject' => 'Hi', 'template' => 'welcome', 'scheduled_at' => 'in 1 hour']);

    expect($email['id'])->toBe('em_9')
        ->and(sentBody($history)['template'])->toBe('welcome');
});
