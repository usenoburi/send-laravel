<?php

declare(strict_types=1);

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Noburi\Send\Client;
use Noburi\Send\Laravel\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Bind a Noburi Send client whose HTTP calls answer with $responses.
 * Returns an ArrayObject filled with the requests sent.
 *
 * @param  list<Response>  $responses
 */
function fakeNoburiSend(array $responses): ArrayObject
{
    $history = new ArrayObject;
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    app()->instance(Client::class, new Client('ps_test_key', [
        'base_url' => 'https://api.example.test',
        'http_client' => new Guzzle(['handler' => $stack]),
    ]));
    app('mail.manager')->forgetMailers();

    return $history;
}

/** @param  array<mixed>  $body */
function jsonResponse(int $status, array $body, array $headers = []): Response
{
    return new Response($status, ['Content-Type' => 'application/json', ...$headers], json_encode($body, JSON_THROW_ON_ERROR));
}

/** @return array<string, mixed> */
function sentBody(ArrayObject $history, int $index = 0): array
{
    return json_decode((string) $history[$index]['request']->getBody(), true);
}
