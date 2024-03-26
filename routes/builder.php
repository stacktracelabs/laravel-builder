<?php

use Illuminate\Support\Facades\Route;

Route::post('/_builder/webhook', \StackTrace\Builder\Http\BuilderWebhookController::class);

Route::get('/test', function () {
    $hook = \StackTrace\Builder\BuilderWebhook::find(34);

    app(\StackTrace\Builder\WebhookProcessor::class)->process($hook);
});
