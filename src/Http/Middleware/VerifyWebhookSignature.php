<?php

declare(strict_types=1);

namespace PulseSend\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PulseSend\Webhook;
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
            (string) config('pulsesend.webhook.secret'),
            (int) config('pulsesend.webhook.tolerance', Webhook::DEFAULT_TOLERANCE),
        );

        if (! $verified) {
            throw new AccessDeniedHttpException('Invalid PulseSend webhook signature.');
        }

        return $next($request);
    }
}
