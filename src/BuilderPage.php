<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Model;

/**
 * @property string $builder_id
 * @property string $model
 * @property string|null $path
 * @property array|null $content
 * @property array|null $builder_data
 * @property array|null $fields
 * @property string|null $locale
 * @property \Carbon\Carbon|null $published_at
 * @property string|null $title
 *
 * @method static \StackTrace\Builder\BuilderPageQueryBuilder query()
 */
class BuilderPage extends Model
{
    protected $guarded = false;

    protected $casts = [
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
        return new BuilderPageQueryBuilder($query);
    }
}
