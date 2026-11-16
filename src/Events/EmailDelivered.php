<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Events;

/** Dispatched for the `email.delivered` webhook event. */
final class EmailDelivered extends PulseSendEvent {}
