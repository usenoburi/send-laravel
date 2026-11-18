<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Events;

/** Dispatched for the `email.sent` webhook event. */
final class EmailSent extends NoburiSendEvent {}
