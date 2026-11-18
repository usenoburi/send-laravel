<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Noburi\Send\Laravel\Events;
use Noburi\Send\Laravel\Http\Middleware\VerifyWebhookSignature;
use Noburi\Send\Webhook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns each Noburi Send webhook call into a Laravel event: `email.delivered`
 * dispatches EmailDelivered. Extend it and add a handle{Type} method
 * (handleEmailDelivered) to take over one type.
 */
class WebhookController extends Controller
{
    /** @var array<string, class-string<Events\NoburiSendEvent>> */
    protected array $events = [
        'email.queued' => Events\EmailQueued::class,
        'email.sent' => Events\EmailSent::class,
        'email.delivered' => Events\EmailDelivered::class,
        'email.delivery_delayed' => Events\EmailDeliveryDelayed::class,
        'email.bounced' => Events\EmailBounced::class,
        'email.complained' => Events\EmailComplained::class,
        'email.failed' => Events\EmailFailed::class,
        'email.canceled' => Events\EmailCanceled::class,
        'email.opened' => Events\EmailOpened::class,
        'email.clicked' => Events\EmailClicked::class,
        'webhook.test' => Events\WebhookTested::class,
    ];

    public function __construct()
    {
        if (config('noburi-send.webhook.secret')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload) || ! isset($payload['type']) || ! is_string($payload['type'])) {
            return new Response('Invalid payload', 400);
        }

        $headers = array_filter([
            'x-pulsesend-signature' => $request->header(Webhook::SIGNATURE_HEADER),
        ]);

        Events\WebhookReceived::dispatch($payload, $headers);

        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload, $headers);
        }

        $event = $this->events[$payload['type']] ?? null;

        if ($event !== null) {
            $event::dispatch($payload, $headers);

            return $this->successMethod();
        }

        return $this->missingMethod($payload);
    }

    /** @param  array<string, mixed>  $parameters */
    protected function successMethod(array $parameters = []): Response
    {
        return new Response('Webhook handled', 200);
    }

    /**
     * An event type this version doesn't know yet: answered 200 so Noburi Send doesn't retry it.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function missingMethod(array $parameters = []): Response
    {
        return new Response;
    }
}
