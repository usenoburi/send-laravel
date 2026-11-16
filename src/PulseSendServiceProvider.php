<?php

declare(strict_types=1);

namespace PulseSend\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PulseSend\Client;
use PulseSend\Laravel\Exceptions\ApiKeyIsMissing;
use PulseSend\Laravel\Transport\PulseSendTransport;

final class PulseSendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pulsesend.php', 'pulsesend');

        $this->app->singleton(Client::class, static function (): Client {
            $apiKey = config('pulsesend.api_key') ?? config('services.pulsesend.key');

            if (! is_string($apiKey) || $apiKey === '') {
                throw ApiKeyIsMissing::create();
            }

            $baseUrl = config('pulsesend.base_url') ?? config('services.pulsesend.base_url');

            return new Client($apiKey, array_filter(['base_url' => is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : null]));
        });

        $this->app->alias(Client::class, 'pulsesend');
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPublishing();

        Mail::extend('pulsesend', fn (array $config = []) => new PulseSendTransport($this->app->make(Client::class), $config['options'] ?? []));
    }

    private function registerRoutes(): void
    {
        if (! config('pulsesend.routes')) {
            return;
        }

        Route::group([
            'domain' => config('pulsesend.domain'),
            'prefix' => config('pulsesend.path'),
            'as' => 'pulsesend.',
            'middleware' => config('pulsesend.middleware', []),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pulsesend.php' => $this->app->configPath('pulsesend.php'),
            ], 'pulsesend-config');
        }
    }
}
