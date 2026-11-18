<?php

declare(strict_types=1);

namespace Noburi\Send\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Noburi\Send\Client;
use Noburi\Send\Laravel\Exceptions\ApiKeyIsMissing;
use Noburi\Send\Laravel\Transport\NoburiSendTransport;

final class NoburiSendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/noburi-send.php', 'noburi-send');

        $this->app->singleton(Client::class, static function (): Client {
            $apiKey = config('noburi-send.api_key') ?? config('services.noburi-send.key');

            if (! is_string($apiKey) || $apiKey === '') {
                throw ApiKeyIsMissing::create();
            }

            $baseUrl = config('noburi-send.base_url') ?? config('services.noburi-send.base_url');

            return new Client($apiKey, array_filter(['base_url' => is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : null]));
        });

        $this->app->alias(Client::class, 'noburi-send');
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPublishing();

        $transport = fn (array $config = []) => new NoburiSendTransport(fn (): Client => $this->app->make(Client::class), $config['options'] ?? []);
        Mail::extend('noburi-send', $transport);
        // The mailer's name in pulsesend/pulsesend-laravel: MAIL_MAILER=pulsesend keeps working.
        Mail::extend('pulsesend', $transport);
    }

    private function registerRoutes(): void
    {
        // null (default): the webhook route exists only once a signing secret is set.
        $routes = config('noburi-send.routes');
        $enabled = $routes === null || $routes === ''
            ? filled(config('noburi-send.webhook.secret'))
            : filter_var($routes, FILTER_VALIDATE_BOOL);

        if (! $enabled) {
            return;
        }

        Route::group([
            'domain' => config('noburi-send.domain'),
            'prefix' => config('noburi-send.path'),
            'as' => 'noburi-send.',
            'middleware' => config('noburi-send.middleware', []),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/noburi-send.php' => $this->app->configPath('noburi-send.php'),
            ], 'noburi-send-config');
        }
    }
}
