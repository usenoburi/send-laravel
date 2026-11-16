<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Events;

/** Dispatched for the `email.queued` webhook event. */
final class EmailQueued extends PulseSendEvent {}
