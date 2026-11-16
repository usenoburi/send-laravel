<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Events;

/** Dispatched for the `email.failed` webhook event. */
final class EmailFailed extends PulseSendEvent {}
