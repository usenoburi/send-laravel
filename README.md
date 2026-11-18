# Noburi Send for Laravel

Send your Laravel mail through [Noburi Send](https://send.noburi.space): your Mailables and notifications keep working as they are.

## Install

```bash
composer require noburi/send-laravel
```

Requires PHP 8.2 and Laravel 11, 12 or 13.

## Configure

```dotenv
NOBURI_SEND_API_KEY=ps_live_xxxxxxxxx
```

Add a mailer in `config/mail.php`:

```php
'mailers' => [
    // ...
    'noburi-send' => [
        'transport' => 'noburi-send',
    ],
],
```

Then use it:

```dotenv
MAIL_MAILER=noburi-send
MAIL_FROM_ADDRESS=hello@acme.com
MAIL_FROM_NAME=Acme
```

## Send

With Laravel's mail, nothing changes:

```php
Mail::to($request->user())->send(new OrderShipped($order));
```

Or with the Noburi Send facade, for everything the API offers (templates, scheduling, batches, tracking):

```php
use Noburi\Send\Laravel\Facades\NoburiSend;

NoburiSend::emails()->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => [$request->user()->email],
    'subject' => 'hello world',
    'html' => (new OrderShipped($order))->render(),
]);
```

The transport sends addresses, subject, bodies, attachments (inline images included), your custom headers and the Mailable's `tags`. The email id comes back in the `X-PulseSend-Email-ID` header of the sent message. Add a `Noburi-Send-Idempotency-Key` header to a message to make retries safe.

## Webhooks

The package answers `POST /noburi-send/webhook` and dispatches a Laravel event per Noburi Send event: `email.delivered` dispatches `Noburi\Send\Laravel\Events\EmailDelivered`.

1. Add `https://your-app.com/noburi-send/webhook` as an endpoint in the Noburi Send dashboard.
2. Copy its signing secret into `.env`:

```dotenv
NOBURI_SEND_WEBHOOK_SECRET=...
```

3. Listen:

```php
Event::listen(function (\Noburi\Send\Laravel\Events\EmailBounced $event) {
    $event->payload['data']['email_id'];
});
```

The route is registered only once `NOBURI_SEND_WEBHOOK_SECRET` is set (`NOBURI_SEND_ROUTES=false` turns it off, `true` keeps it without a secret). It sits outside the `web` middleware group, so CSRF doesn't apply.

Without `NOBURI_SEND_API_KEY`, the app still boots: only an actual send throws `ApiKeyIsMissing`.

## License

MIT

## Moving from pulsesend/pulsesend-laravel

This package is the same code under its new name. Nothing breaks while you switch:

- `composer remove pulsesend/pulsesend-laravel && composer require noburi/send-laravel`.
- Replace `PulseSend\Laravel\` with `Noburi\Send\Laravel\` in your imports, and the `PulseSend` facade with `NoburiSend`.
- The `PULSESEND_*` variables are still read when the `NOBURI_SEND_*` ones are missing, `MAIL_MAILER=pulsesend` still works, and so does the `PulseSend-Idempotency-Key` header.
- The webhook route moves to `/noburi-send/webhook`: update the endpoint in the dashboard, or keep the old address with `NOBURI_SEND_PATH=pulsesend`.
- If you published `config/pulsesend.php`, publish the new file (`php artisan vendor:publish --tag=noburi-send-config`) and delete the old one.
