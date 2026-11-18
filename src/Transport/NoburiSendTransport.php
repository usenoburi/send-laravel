<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Transport;

use Closure;
use Noburi\Send\Client;
use Noburi\Send\Exception\NoburiSendException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends Laravel mail through POST /api/v1/emails. Only what Laravel already
 * gives is read: addresses, subject, bodies, attachments, headers and tags.
 */
class NoburiSendTransport extends AbstractTransport
{
    /** Headers the API sets itself, or that this transport reads instead of forwarding. */
    private const BYPASSED_HEADERS = [
        'from', 'to', 'cc', 'bcc', 'subject', 'reply-to', 'sender', 'return-path', 'message-id', 'date',
        'mime-version', 'content-type', 'content-transfer-encoding', 'content-disposition', 'dkim-signature', 'received',
        'x-tag', 'noburi-send-idempotency-key', 'pulsesend-idempotency-key',
    ];

    /**
     * The client is resolved at the first send, so an app without a key can
     * still build its mailers: only an actual send needs NOBURI_SEND_API_KEY.
     *
     * @param  Client|Closure(): Client  $client
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected Client|Closure $client,
        protected array $config = [],
    ) {
        parent::__construct();
    }

    protected function client(): Client
    {
        if ($this->client instanceof Closure) {
            $this->client = ($this->client)();
        }

        return $this->client;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $headers = [];
        $tags = [];

        foreach ($email->getHeaders()->all() as $name => $header) {
            if ($header instanceof TagHeader) {
                $tags[] = $header->getValue();

                continue;
            }

            if (in_array(strtolower($name), self::BYPASSED_HEADERS, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $options = [];
        // PulseSend-Idempotency-Key: the header's name in pulsesend/pulsesend-laravel, still read.
        foreach (['Noburi-Send-Idempotency-Key', 'PulseSend-Idempotency-Key'] as $name) {
            if ($email->getHeaders()->has($name)) {
                $options['idempotency_key'] = $email->getHeaders()->get($name)?->getBodyAsString();
                break;
            }
        }

        $payload = array_filter([
            'from' => $this->sender($email, $envelope),
            'to' => $this->addresses($this->recipients($email, $envelope)),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'reply_to' => ($email->getReplyTo()[0] ?? null)?->getAddress(),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody() === null ? null : $this->body($email->getHtmlBody()),
            'text' => $email->getTextBody() === null ? null : $this->body($email->getTextBody()),
            'headers' => $headers,
            'tags' => array_values(array_unique($tags)),
            'attachments' => $this->attachments($email),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        $client = $this->client();

        try {
            $result = $client->emails->send($payload, $options);
        } catch (NoburiSendException $exception) {
            throw new TransportException(
                sprintf('Request to the Noburi Send API failed. Reason: %s', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception,
            );
        }

        $id = $result['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new TransportException('Request to the Noburi Send API failed. Reason: the answer has no email id.');
        }

        $original = $message->getOriginalMessage();
        if ($original instanceof Message) {
            $original->getHeaders()->addTextHeader('X-PulseSend-Email-ID', $id);
        }
        $message->setMessageId($id);
    }

    /** "Name <address>" from the From header, else the envelope sender. */
    protected function sender(Email $email, Envelope $envelope): string
    {
        $from = $email->getFrom()[0] ?? $envelope->getSender();
        $name = trim(str_replace(['"', '<', '>', "\r", "\n"], '', $from->getName()));

        return $name === '' ? $from->getAddress() : $name.' <'.$from->getAddress().'>';
    }

    /**
     * The envelope recipients without the CC and BCC ones.
     *
     * @return list<Address>
     */
    protected function recipients(Email $email, Envelope $envelope): array
    {
        $copied = array_map(
            static fn (Address $address): string => strtolower($address->getAddress()),
            [...$email->getCc(), ...$email->getBcc()],
        );

        return array_values(array_filter(
            $envelope->getRecipients(),
            static fn (Address $address): bool => ! in_array(strtolower($address->getAddress()), $copied, true),
        ));
    }

    /**
     * @param  array<Address>  $addresses
     * @return list<string>
     */
    protected function addresses(array $addresses): array
    {
        return array_values(array_unique(array_map(static fn (Address $address): string => $address->getAddress(), $addresses)));
    }

    /**
     * Base64 content, file name and type; inline parts keep their content id for cid: links.
     *
     * @return list<array<string, string>>
     */
    protected function attachments(Email $email): array
    {
        $items = [];

        foreach ($email->getAttachments() as $attachment) {
            $item = [
                'filename' => $attachment->getFilename() ?? 'attachment',
                'content' => base64_encode($attachment->getBody()),
                'content_type' => $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
            ];

            if ($attachment->getPreparedHeaders()->getHeaderBody('Content-Disposition') === 'inline') {
                $item['content_id'] = $attachment->getContentId();
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  resource|string  $body
     */
    private function body(mixed $body): string
    {
        return is_resource($body) ? (string) stream_get_contents($body, -1, 0) : $body;
    }

    public function __toString(): string
    {
        return 'noburi-send';
    }
}
