<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Builder;

class BuilderPageQueryBuilder extends Builder
{
    /**
     * Add clause to query only published pages.
     */
    public function published(): static
    {
        return $this->where('published_at', '<=', now());
    }

    /**
     * Add clause to query only page with given path.
     */
    public function withPath(string $url): static
    {
        return $this->where('path', $url);
    }

    /**
     * Add clause to query only pages with specific locale.
     */
    public function forLocale(string $locale): static
    {
        return $this->where('locale', $locale);
    }

    /**
     * Add clause to query only pages without specific locale.
     */
    public function withoutLocale(): static
    {
        return $this->whereNull('locale');
    }
}
