<?php

use Illuminate\Support\Facades\Route;
use PulseSend\Laravel\Http\Controllers\WebhookController;

Route::post('webhook', [WebhookController::class, 'handleWebhook'])->name('webhook');
