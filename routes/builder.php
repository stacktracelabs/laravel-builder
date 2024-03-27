<?php

use Illuminate\Support\Facades\Route;

Route::post(config('builder.webhook_path'), \StackTrace\Builder\Http\BuilderWebhookController::class);
