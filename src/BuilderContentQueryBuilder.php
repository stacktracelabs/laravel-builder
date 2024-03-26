<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Builder;

/**
 * @method \StackTrace\Builder\BuilderContent|null first($columns = ['*'])
 */
class BuilderContentQueryBuilder extends Builder
{
    /**
     * Add clause to query only content of given type.
     */
    public function type(ContentType $type): static
    {
        return $this->where('type', $type);
    }

    /**
     * Add clause to query only pages.
     */
    public function pages(): static
    {
        return $this->type(ContentType::Page);
    }

    /**
     * Add clause to query only sections.
     */
    public function sections(): static
    {
        return $this->type(ContentType::Section);
    }

    /**
     * Add clause to query only published content.
     */
    public function published(): static
    {
        return $this->where('published_at', '<=', now());
    }

    /**
     * Add clause to query only content with given path.
     */
    public function withPath(string $url): static
    {
        return $this->where('path', $url);
    }

    /**
     * Add clause to query only content without path.
     */
    public function withoutPath(): static
    {
        return $this->whereNull('path');
    }

    /**
     * Add clause to query only content with specific locale.
     */
    public function forLocale(string $locale): static
    {
        return $this->where('locale', $locale);
    }

    /**
     * Add clause to query only content without specific locale.
     */
    public function withoutLocale(): static
    {
        return $this->whereNull('locale');
    }
}
