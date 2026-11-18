<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Events;

/** Dispatched for the `email.delivery_delayed` webhook event. */
final class EmailDeliveryDelayed extends NoburiSendEvent {}
