<?php

declare(strict_types=1);

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Noburi\Send\Client;
use Noburi\Send\Laravel\Exceptions\ApiKeyIsMissing;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Part\DataPart;

final class OrderShipped extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your order shipped', tags: ['orders'], metadata: ['order_id' => '42']);
    }

    public function headers(): Headers
    {
        return new Headers(text: ['X-Campaign' => 'autumn', 'Noburi-Send-Idempotency-Key' => 'order-42']);
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>On its way</p>');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => "id,total\n42,10\n", 'invoice.csv')->withMime('text/csv')];
    }
}

it('sends a Mailable through the API without changing the app code', function () {
    $history = fakeNoburiSend([jsonResponse(202, ['object' => 'email', 'id' => 'em_123', 'status' => 'queued'])]);

    $sent = Mail::to('jane@example.com')
        ->cc('boss@example.com')
        ->bcc('audit@example.com')
        ->send((new OrderShipped)->replyTo('support@acme.com', 'Support'));

    $request = $history[0]['request'];
    $body = sentBody($history);

    expect((string) $request->getUri())->toBe('https://api.example.test/api/v1/emails')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer ps_test_key')
        ->and($request->getHeaderLine('Idempotency-Key'))->toBe('order-42')
        ->and($body['from'])->toBe('Acme <hello@acme.com>')
        ->and($body['to'])->toBe(['jane@example.com'])
        ->and($body['cc'])->toBe(['boss@example.com'])
        ->and($body['bcc'])->toBe(['audit@example.com'])
        ->and($body['reply_to'])->toBe('support@acme.com')
        ->and($body['subject'])->toBe('Your order shipped')
        ->and($body['html'])->toContain('On its way')
        ->and($body['tags'])->toBe(['orders'])
        ->and($body['headers'])->toMatchArray(['X-Campaign' => 'autumn', 'X-Metadata-order_id' => '42'])
        ->and($body['headers'])->not->toHaveKey('Noburi-Send-Idempotency-Key')
        ->and($body['attachments'][0])->toBe([
            'filename' => 'invoice.csv',
            'content' => base64_encode("id,total\n42,10\n"),
            'content_type' => 'text/csv',
        ]);

    expect($sent->getMessageId())->toBe('em_123')
        ->and($sent->getOriginalMessage()->getHeaders()->get('X-PulseSend-Email-ID')->getBodyAsString())->toBe('em_123');
});

it('keeps the content id of inline images', function () {
    $history = fakeNoburiSend([jsonResponse(202, ['id' => 'em_1'])]);

    Mail::html('<img src="cid:logo">', function ($message) {
        $message->to('jane@example.com')->subject('Logo');
        $message->getSymfonyMessage()->addPart(
            (new DataPart('PNGDATA', 'logo.png', 'image/png'))->asInline()->setContentId('logo@acme')
        );
    });

    $attachment = sentBody($history)['attachments'][0];
    expect($attachment['content_id'])->toBe('logo@acme')
        ->and($attachment['content_type'])->toBe('image/png');
});

it('sends plain text mail and no empty fields', function () {
    $history = fakeNoburiSend([jsonResponse(202, ['id' => 'em_1'])]);

    Mail::raw('Hello', fn ($message) => $message->to('jane@example.com')->subject('Hi'));

    $body = sentBody($history);
    expect($body['text'])->toBe('Hello')
        ->and($body)->not->toHaveKeys(['html', 'cc', 'bcc', 'tags', 'attachments', 'reply_to']);
});

it('reports API errors as transport errors', function () {
    fakeNoburiSend([jsonResponse(422, ['error' => [
        'type' => 'validation_error', 'code' => 'domain_not_verified', 'message' => 'acme.com is not verified yet.', 'param' => 'from',
    ]])]);

    expect(fn () => Mail::raw('Hello', fn ($m) => $m->to('jane@example.com')->subject('Hi')))
        ->toThrow(TransportException::class, 'Request to the Noburi Send API failed. Reason: acme.com is not verified yet.');
});

it('builds the mailer without a key and only fails when sending', function () {
    config(['noburi-send.api_key' => null]);
    app()->forgetInstance(Client::class);
    app('mail.manager')->forgetMailers();

    expect(Mail::mailer('noburi-send'))->toBeInstanceOf(Mailer::class);

    expect(fn () => Mail::raw('Hello', fn ($m) => $m->to('jane@example.com')->subject('Hi')))
        ->toThrow(ApiKeyIsMissing::class, 'NOBURI_SEND_API_KEY');
});

it('keeps the names of pulsesend/pulsesend-laravel: the pulsesend mailer and its idempotency header', function () {
    $history = fakeNoburiSend([jsonResponse(202, ['id' => 'em_7'])]);
    config(['mail.mailers.pulsesend' => ['transport' => 'pulsesend']]);

    Mail::mailer('pulsesend')->raw('Hi', function ($message) {
        $message->from('hello@acme.com')->to('jane@example.com')->subject('Hi');
        $message->getHeaders()->addTextHeader('PulseSend-Idempotency-Key', 'legacy-1');
    });

    expect($history[0]['request']->getHeaderLine('Idempotency-Key'))->toBe('legacy-1')
        ->and(sentBody($history)['headers'] ?? [])->not->toHaveKey('PulseSend-Idempotency-Key');
});
