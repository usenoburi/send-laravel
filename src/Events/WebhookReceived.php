<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Events;

/** Dispatched for every webhook call, whatever its type, before the typed event. */
final class WebhookReceived extends NoburiSendEvent {}
