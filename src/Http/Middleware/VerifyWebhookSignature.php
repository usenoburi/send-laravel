<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Noburi\Send\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VerifyWebhookSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $verified = Webhook::verify(
            $request->getContent(),
            $request->header(Webhook::SIGNATURE_HEADER),
            (string) config('noburi-send.webhook.secret'),
            (int) config('noburi-send.webhook.tolerance', Webhook::DEFAULT_TOLERANCE),
        );

        if (! $verified) {
            throw new AccessDeniedHttpException('Invalid Noburi Send webhook signature.');
        }

        return $next($request);
    }
}
