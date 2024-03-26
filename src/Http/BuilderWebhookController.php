<?php


namespace StackTrace\Builder\Http;


use Illuminate\Http\Request;
use StackTrace\Builder\BuilderWebhook;
use StackTrace\Builder\Jobs\ProcessWebhookJob;

class BuilderWebhookController
{
    public function __invoke(Request $request)
    {
        $webhook = BuilderWebhook::create([
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        ProcessWebhookJob::dispatch($webhook);

        return response()->json([
            'message' => 'ok',
        ]);
    }
}
