<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Builder;

class BuilderSectionQueryBuilder extends Builder
{
    /**
     * Add clause to query only published sections.
     */
    public function published(): static
    {
        return $this->where('published_at', '<=', now());
    }

    /**
     * Add clause to query only section with given path.
     */
    public function withPath(string $url): static
    {
        return $this->where('path', $url);
    }

    /**
     * Add clause to query only sections without path.
     */
    public function withoutPath(): static
    {
        return $this->whereNull('path');
    }

    /**
     * Add clause to query only sections with specific locale.
     */
    public function forLocale(string $locale): static
    {
        return $this->where('locale', $locale);
    }

    /**
     * Add clause to query only sections without specific locale.
     */
    public function withoutLocale(): static
    {
        return $this->whereNull('locale');
    }
}
