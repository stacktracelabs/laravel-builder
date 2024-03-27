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

                if ($locale != $fallbackLocale) {
                    $query->orWhereIn('locale', [$locale, $fallbackLocale]);
                } else {
                    $query->orWhere('locale', $locale);
                }
            })
            ->get()
            // For each model, we select single section.
            ->groupBy('model')->map(function (Collection $sections) use ($locale, $fallbackLocale) {
                $localized = $sections->firstWhere('locale', $locale);

                if ($localized instanceof BuilderContent) {
                    return $localized;
                }

                if ($locale != $fallbackLocale) {
                    $fallback = $sections->firstWhere('locale', $fallbackLocale);

                    if ($fallback instanceof BuilderContent) {
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
        $path = '/'.ltrim($request->path(), '/');

        $localizedPage = BuilderContent::query()
            ->pages()
            ->published()
            ->withPath($path)
            ->forLocale(App::getLocale())
            ->first();

        if ($localizedPage instanceof BuilderContent) {
            return $localizedPage;
        }

        if (App::getLocale() != App::getFallbackLocale()) {
            $fallbackPage = BuilderContent::query()
                ->pages()
                ->published()
                ->withPath($path)
                ->forLocale(App::getFallbackLocale())
                ->first();

            if ($fallbackPage instanceof BuilderContent) {
                return $fallbackPage;
            }
        }

        return BuilderContent::query()
            ->pages()
            ->published()
            ->withPath($path)
            ->withoutLocale()
            ->first();
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
}
