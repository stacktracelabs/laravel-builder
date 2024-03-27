<?php


namespace StackTrace\Builder\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use StackTrace\Builder\BuilderWebhook;
use StackTrace\Builder\ContentFactory;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public BuilderWebhook $webhook
    ) { }

    public function handle(ContentFactory $factory): void
    {
        try {
            $this->webhook->process($factory);
        } catch (\Throwable $e) {
            $this->webhook->exception = $e->getTraceAsString();
        } finally {
            $this->webhook->processed_at = now();
            $this->webhook->save();
        }
    }
}
