<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Exceptions;

use InvalidArgumentException;

final class ApiKeyIsMissing extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('The Noburi Send API key is missing. Set NOBURI_SEND_API_KEY in your .env file.');
    }
}
