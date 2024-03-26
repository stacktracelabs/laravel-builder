<?php


namespace StackTrace\Builder;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BuilderService
{
    /**
     * Resolve editor data from incomming request.
     * The request must have path, locale and model parameters set.
     */
    public function resolveEditorFromRequest(Request $request): ?BuilderEditor
    {
        $locale = $request->input('locale', App::getLocale());

        $path = Utils::normalizePath($request->input('path', "/"));

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
     * Resolve page from incomming request.
     */
    public function resolvePageFromRequest(Request $request): ?BuilderPage
    {
        $path = '/'.ltrim($request->path(), '/');

        $localizedPage = BuilderPage::query()
            ->published()
            ->withPath($path)
            ->forLocale(App::getLocale())
            ->first();

        if ($localizedPage instanceof BuilderPage) {
            return $localizedPage;
        }

        if (App::getLocale() != App::getFallbackLocale()) {
            $fallbackPage = BuilderPage::query()
                ->published()
                ->withPath($path)
                ->forLocale(App::getFallbackLocale())
                ->first();

            if ($fallbackPage instanceof BuilderPage) {
                return $fallbackPage;
            }
        }

        return BuilderPage::query()
            ->published()
            ->withPath($path)
            ->withoutLocale()
            ->first();
    }
}
