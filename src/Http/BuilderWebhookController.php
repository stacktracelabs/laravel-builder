<?php


namespace StackTrace\Builder\Http;


use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use StackTrace\Builder\BuilderWebhook;
use StackTrace\Builder\Jobs\ProcessWebhookJob;

class BuilderWebhookController
{
    public function __invoke(Request $request)
    {
        $token = config('builder.webhook_token');

        if (is_string($token) && Str::length($token) > 0) {
            $authorization = $request->header('authorization');

            abort_unless(is_string($authorization) && Str::length($authorization) > 0 && $token === $authorization, 401, "The authorization token is invalid.");
        }

        $webhook = BuilderWebhook::create([
            'url' => $request->url(),
            'headers' => Arr::except($request->headers->all(), ['authorization']),
            'payload' => $request->all(),
        ]);

        ProcessWebhookJob::dispatch($webhook);

        return response()->json([
            'message' => 'ok',
        ]);
    }
}
