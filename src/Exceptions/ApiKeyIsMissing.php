<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Exceptions;

use InvalidArgumentException;

final class ApiKeyIsMissing extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('The PulseSend API key is missing. Set PULSESEND_API_KEY in your .env file.');
    }
}
