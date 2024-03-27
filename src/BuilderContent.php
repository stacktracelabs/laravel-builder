<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @property \StackTrace\Builder\ContentType $type
 * @property string $builder_id
 * @property string $model
 * @property string|null $name
 * @property string|null $path
 * @property array|null $content
 * @property array|null $builder_data
 * @property array|null $fields
 * @property string|null $locale
 * @property \Carbon\Carbon|null $published_at
 * @property string|null $title
 *
 * @method static \StackTrace\Builder\BuilderContentQueryBuilder query()
 */
class BuilderContent extends Model
{
    protected $guarded = false;

    protected $casts = [
        'type' => ContentType::class,
        'content' => 'array',
        'builder_data' => 'array',
        'fields' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Determine if the page is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at != null && $this->published_at->lte(now());
    }

    /**
     * Make the page published.
     */
    public function publish(): void
    {
        $this->published_at = now();
    }

    /**
     * Make the page unpublished.
     */
    public function unpublish(): void
    {
        $this->published_at = null;
    }

    public function newEloquentBuilder($query)
    {
        return new BuilderContentQueryBuilder($query);
    }

    /**
     * Retrieve the field value.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        return Arr::get($this->fields ?: [], $name, $default);
    }

    /**
     * Retrieve the field value as boolean.
     */
    public function booleanField(string $name, bool $default = false): bool
    {
        return filter_var($this->field($name, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
