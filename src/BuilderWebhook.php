<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @property string $url
 * @property array $headers
 * @property array $payload
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $exception
 */
class BuilderWebhook extends Model
{
    protected $guarded = false;

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Process the webhook by creating new content.
     */
    public function process(ContentFactory $factory): void
    {
        $payload = Arr::get($this->payload, 'newValue');

        if (is_array($payload)) {
            $factory->create($payload);
        }
    }
}
