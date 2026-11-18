<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Events;

/** Dispatched for the `email.failed` webhook event. */
final class EmailFailed extends NoburiSendEvent {}
