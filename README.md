# PulseSend for Laravel

Send your Laravel mail through [PulseSend](https://send.noburi.space): your Mailables and notifications keep working as they are.

## Install

```bash
composer require pulsesend/pulsesend-laravel
```

Requires PHP 8.2 and Laravel 11, 12 or 13.

## Configure

```dotenv
PULSESEND_API_KEY=ps_live_xxxxxxxxx
```

Add a mailer in `config/mail.php`:

```php
'mailers' => [
    // ...
    'pulsesend' => [
        'transport' => 'pulsesend',
    ],
],
```

Then use it:

```dotenv
MAIL_MAILER=pulsesend
MAIL_FROM_ADDRESS=hello@acme.com
MAIL_FROM_NAME=Acme
```

## Send

With Laravel's mail, nothing changes:

```php
Mail::to($request->user())->send(new OrderShipped($order));
```

Or with the PulseSend facade, for everything the API offers (templates, scheduling, batches, tracking):

```php
use PulseSend\Laravel\Facades\PulseSend;

PulseSend::emails()->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => [$request->user()->email],
    'subject' => 'hello world',
    'html' => (new OrderShipped($order))->render(),
]);
```

The transport sends addresses, subject, bodies, attachments (inline images included), your custom headers and the Mailable's `tags`. The email id comes back in the `X-PulseSend-Email-ID` header of the sent message. Add a `PulseSend-Idempotency-Key` header to a message to make retries safe.

## Webhooks

The package answers `POST /pulsesend/webhook` and dispatches a Laravel event per PulseSend event: `email.delivered` dispatches `PulseSend\Laravel\Events\EmailDelivered`.

1. Add `https://your-app.com/pulsesend/webhook` as an endpoint in the PulseSend dashboard.
2. Copy its signing secret into `.env`:

```dotenv
PULSESEND_WEBHOOK_SECRET=...
```

3. Listen:

```php
Event::listen(function (\PulseSend\Laravel\Events\EmailBounced $event) {
    $event->payload['data']['email_id'];
});
```

The route is registered only once `PULSESEND_WEBHOOK_SECRET` is set (`PULSESEND_ROUTES=false` turns it off, `true` keeps it without a secret). It sits outside the `web` middleware group, so CSRF doesn't apply.

Without `PULSESEND_API_KEY`, the app still boots: only an actual send throws `ApiKeyIsMissing`.

## License

MIT
