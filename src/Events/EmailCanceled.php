<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Events;

/** Dispatched for the `email.canceled` webhook event. */
final class EmailCanceled extends PulseSendEvent {}
