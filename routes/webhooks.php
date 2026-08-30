<?php

use App\Http\Controllers\Webhooks\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])->middleware('throttle:webhook')->name('webhooks.xendit');
