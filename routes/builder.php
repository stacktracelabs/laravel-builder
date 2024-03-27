<?php

use Illuminate\Support\Facades\Route;

Route::post(config('builder.webhook_path'), \StackTrace\Builder\Http\BuilderWebhookController::class);

// Route::get('/test', function () {
//     // $hook = \StackTrace\Builder\BuilderWebhook::find(7);
//
//     // app(\StackTrace\Builder\WebhookProcessor::class)->process($hook);
//
//     (new \StackTrace\Builder\ContentFactory())->createFromContentAPI(
//         \StackTrace\Builder\BuilderContent::find(2)->builder_data
//     );
//
//     dd('DONE');
// });
