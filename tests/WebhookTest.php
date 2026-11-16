<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use PulseSend\Laravel\Events\EmailBounced;
use PulseSend\Laravel\Events\EmailDelivered;
use PulseSend\Laravel\Events\WebhookReceived;
use PulseSend\Webhook;

function callWebhook(string $body, ?string $signature = null)
{
    $server = ['CONTENT_TYPE' => 'application/json'];
    if ($signature !== null) {
        $server['HTTP_X_PULSESEND_SIGNATURE'] = $signature;
    }

    return test()->call('POST', '/pulsesend/webhook', [], [], [], $server, $body);
}

$body = json_encode(['id' => 'evt_1', 'type' => 'email.delivered', 'created_at' => '2026-11-16T10:00:00Z', 'data' => ['email_id' => 'em_1', 'status' => 'delivered']]);

it('dispatches a Laravel event for each event type', function () use ($body) {
    Event::fake();

    callWebhook($body)->assertOk();

    Event::assertDispatched(WebhookReceived::class);
    Event::assertDispatched(EmailDelivered::class, fn (EmailDelivered $e) => $e->payload['data']['email_id'] === 'em_1');
    Event::assertNotDispatched(EmailBounced::class);
});

it('checks the signature when a secret is set', function () use ($body) {
    config(['pulsesend.webhook.secret' => 'whsec_test']);
    Event::fake();

    callWebhook($body)->assertForbidden();
    callWebhook($body, Webhook::sign($body, 'wrong'))->assertForbidden();
    callWebhook($body, Webhook::sign($body, 'whsec_test', time() - 600))->assertForbidden();
    callWebhook($body, Webhook::sign($body, 'whsec_test'))->assertOk();

    Event::assertDispatchedTimes(EmailDelivered::class, 1);
});

it('answers 200 to an unknown type and 400 to a bad body', function () {
    Event::fake();

    callWebhook(json_encode(['id' => 'evt_2', 'type' => 'contact.created']))->assertOk();
    callWebhook('not json')->assertStatus(400);

    Event::assertDispatched(WebhookReceived::class, 1);
});

it('sits under the configured path and outside CSRF', function () {
    expect(route('pulsesend.webhook', absolute: false))->toBe('/pulsesend/webhook');
});
