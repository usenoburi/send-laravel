<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Events;

/** Dispatched for the `email.bounced` webhook event. */
final class EmailBounced extends NoburiSendEvent {}
