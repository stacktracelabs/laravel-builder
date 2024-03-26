<?php


namespace StackTrace\Builder;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

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

        $model = $request->input('model');

        if (! $model) {
            return null;
        }

        return new BuilderEditor(
            apiKey: $key,
            url: $path,
            model: $model,
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
}
