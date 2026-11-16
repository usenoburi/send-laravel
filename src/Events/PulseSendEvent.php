<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A webhook call from PulseSend. `payload` is the decoded body:
 * { id, type, created_at, data: { email_id, status, ... } }.
 */
abstract class PulseSendEvent
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public array $payload,
        public array $headers = [],
    ) {}
}
