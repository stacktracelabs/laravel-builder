<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BuilderService
{
    /**
     * Retrieve collection of sections for incoming request.
     */
    public function getSectionsForRequest(Request $request): Collection
    {
        $locale = App::getLocale();
        $fallbackLocale = App::getFallbackLocale();

        // Retrieve all sections which can be rendered for given request.
        return BuilderContent::query()
            ->sections()
            ->published()
            ->where(function (Builder $query) use ($request) {
                $query->whereNull('path')->orWhere('path', Utils::normalizePath($request->path()));
            })
            ->where(function (Builder $query) use ($locale, $fallbackLocale) {
                $query->whereNull('locale');

                if ($locale != $fallbackLocale && $this->shouldUseFallbackLocale()) {
                    $query->orWhereIn('locale', [$locale, $fallbackLocale]);
                } else {
                    $query->orWhere('locale', $locale);
                }
            })
            ->get()
            // For each model, we select single section.
            ->groupBy('model')->map(function (Collection $sections) use ($locale, $fallbackLocale) {
                if ($localized = $sections->firstWhere('locale', $locale)) {
                    return $localized;
                }

                if ($locale != $fallbackLocale && $this->shouldUseFallbackLocale()) {
                    if ($fallback = $sections->firstWhere('locale', $fallbackLocale)) {
                        return $fallback;
                    }
                }

                return $sections->firstWhere('locale', null);
            })->filter()->values();
    }

    /**
     * Resolve editor data from incoming request.
     * The request must have path, locale and model parameters set.
     */
    public function resolveEditorFromRequest(Request $request): ?BuilderEditor
    {
        $locale = $request->input('locale') ?: App::getLocale();

        $path = Utils::normalizePath($request->input('path') ?: '/');

        $key = config('builder.api_key');

        $modelRef = $request->input('model');

        if(! $modelRef) {
            return null;
        }

        $model = $this->getModelById($modelRef) ?: $this->getModelByName($modelRef);

        if (! $model) {
            return null;
        }

        return new BuilderEditor(
            apiKey: $key,
            url: $path,
            model: $model->name,
            locale: $locale
        );
    }

    /**
     * Resolve page from incoming request.
     */
    public function resolvePageFromRequest(Request $request): ?BuilderContent
    {
        $locale = App::getLocale();
        $fallbackLocale = App::getFallbackLocale();

        $pages = BuilderContent::query()
            ->pages()
            ->published()
            ->withPath(Utils::normalizePath($request->path()))
            ->where(function (Builder $query) use ($locale, $fallbackLocale) {
                $query->whereNull('locale');

                if ($locale != $fallbackLocale && $this->shouldUseFallbackLocale()) {
                    $query->orWhereIn('locale', [$locale, $fallbackLocale]);
                } else {
                    $query->orWhere('locale', $locale);
                }
            })
            ->get();

        // Search for localized page.
        if ($localizedPage = $pages->firstWhere('locale', $locale)) {
            return $localizedPage;
        }

        // If fallback is enabled, search for fallback locale.
        if ($locale != $fallbackLocale && $this->shouldUseFallbackLocale()) {
            if ($fallbackPage = $pages->firstWhere('locale', $fallbackLocale)) {
                return $fallbackPage;
            }
        }

        // Default to page without locale.
        return $pages->firstWhere('locale', null);
    }

    /**
     * Retrieve model by its identifier.
     */
    public function getModelById(string $id): ?BuilderModel
    {
        $apiKey = config('builder.api_key');

        $key = "BuilderModelById:{$id}:{$apiKey}";

        if (Cache::has($key)) {
            return BuilderModel::fromArray(Cache::get($key));
        }

        if ($model = $this->getModels()->firstWhere('id', $id)) {
            Cache::forever($key, $model->toArray());

            return $model;
        }

        return null;
    }

    /**
     * Retrieve model by ids name.
     */
    public function getModelByName(string $name): ?BuilderModel
    {
        $apiKey = config('builder.api_key');

        $key = "BuilderModelByName:{$name}:{$apiKey}";

        if (Cache::has($key)) {
            return BuilderModel::fromArray(Cache::get($key));
        }

        if ($model = $this->getModels()->firstWhere('name', $name)) {
            Cache::forever($key, $model->toArray());

            return $model;
        }

        return null;
    }

    /**
     * Retrieve available models.
     * @return Collection<int, \StackTrace\Builder\BuilderModel>
     */
    public function getModels(): Collection
    {
        $response = Http::withHeader("Authorization", "Bearer ".config('builder.private_key'))
            ->post("https://builder.io/api/v2/admin", [
                'query' => "query { models { id name } }",
            ]);

        return $response->collect('data.models')->map(fn (array $it) => BuilderModel::fromArray($it));
    }

    /**
     * Retrieve all content entries for given model.
     */
    public function getContentEntriesByModelName(string $name): Collection
    {
        $fetchPage = function (int $offset = 0) use ($name) {
            $key = config('builder.api_key');

            $url = "https://cdn.builder.io/api/v3/content/{$name}?apiKey={$key}&limit=100";

            if ($offset > 0) {
                $url .= "&offset={$offset}";
            }

            return Http::get($url)->collect('results');
        };

        $total = collect();

        $offset = 0;

        do {
            $results = $fetchPage($offset);

            if ($results->isEmpty()) {
                break;
            }

            $total = $total->merge($results);

            $offset = $total->count();
        } while (true);

        return $total;
    }

    /**
     * Determine if fallback locale should be used.
     */
    protected function shouldUseFallbackLocale(): bool
    {
        return config('builder.use_fallback_locale', false);
    }
}
