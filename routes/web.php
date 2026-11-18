<?php

use Illuminate\Support\Facades\Route;
use Noburi\Send\Laravel\Http\Controllers\WebhookController;

Route::post('webhook', [WebhookController::class, 'handleWebhook'])->name('webhook');
