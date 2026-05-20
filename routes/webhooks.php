<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
