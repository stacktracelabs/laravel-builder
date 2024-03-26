<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Model;

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
}
