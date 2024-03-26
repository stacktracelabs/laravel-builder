<?php


namespace StackTrace\Builder;


class WebhookProcessor
{
    public function __construct(
        protected ContentFactory $factory
    ) { }

    /**
     * Process the Builder webhook.
     */
    public function process(BuilderWebhook $webhook): void
    {
        $this->factory->createFromWebhook($webhook->payload);
    }
}
