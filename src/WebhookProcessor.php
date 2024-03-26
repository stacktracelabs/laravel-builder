<?php


namespace StackTrace\Builder;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class WebhookProcessor
{
    /**
     * Process the Builder webhook.
     */
    public function process(BuilderWebhook $webhook): void
    {
        $payload = Arr::get($webhook->payload, 'newValue');

        if (! is_array($payload)) {
            return;
        }

        if (Arr::get($payload, 'meta.kind') == 'page') {
            $this->processPage($payload);
        } else if (Arr::get($payload, 'meta.kind') == 'component') {
            $this->processSection($payload);
        }
    }

    protected function processSection(array $payload): void
    {
        $id = Arr::get($payload, 'id');
        if (! $id) {
            return;
        }

        $modelId = Arr::get($payload, 'modelId');

        if (! $modelId) {
            return;
        }

        $modelName = $this->resolveModelName($modelId);
        if (! $modelName) {
            return;
        }

        $title = Arr::get($payload, 'name');
        $path = $this->resolveUrl($payload);
        $content = $this->resolveContent($payload);

        if (! $content) {
            return;
        }

        $locale = $this->resolveLocale($payload);

        $isPublished = Arr::get($payload, 'published') == 'published';

        /** @var \StackTrace\Builder\BuilderSection $section */
        $section = BuilderSection::query()->firstWhere('builder_id', $id) ?: new BuilderSection([
            'builder_id' => $id,
        ]);

        $section->fill([
            'model' => $modelName,
            'title' => $title,
            'path' => $path,
            'content' => $content,
            'builder_data' => $payload,
            'locale' => $locale,
        ]);

        if ($isPublished != $section->isPublished()) {
            if ($isPublished) {
                $section->publish();
            } else {
                $section->unpublish();
            }
        }

        $section->save();
    }

    protected function resolveModelName(string $id): ?string
    {
        $response = Http::withHeader("Authorization", "Bearer ".config('builder.private_key'))
            ->post("https://builder.io/api/v2/admin", [
                'query' => "query { models { id name } }",
            ]);

        $model = $response->collect('data.models')->firstWhere('id', $id);

        if ($model) {
            // TODO: Might cache
            return $model['name'];
        }

        return null;
    }

    protected function processPage(array $payload): void
    {
        $model = Arr::get($payload, 'modelId');

        if (! $model || ! $this->isModelValid($model)) {
            return;
        }

        $locale = $this->resolveLocaleForModel($model);
        $pageId = Arr::get($payload, 'id');
        if (! $pageId) {
            return;
        }

        $url = $this->resolveUrl($payload);

        $content = $this->resolveContent($payload);

        $isPublished = Arr::get($payload, 'published') == 'published';

        $title = Arr::get($payload, 'data.title');

        /** @var \StackTrace\Builder\BuilderPage $page */
        $page = BuilderPage::query()->firstWhere('page_id', $pageId) ?: new BuilderPage([
            'page_id' => $pageId,
        ]);

        $page->fill([
            'path' => $url,
            'content' => $content,
            'builder_data' => $payload,
            'locale' => $locale,
            'title' => $title,
        ]);

        if ($page->isPublished() != $isPublished) {
            if ($isPublished) {
                $page->publish();
            } else {
                $page->unpublish();
            }
        }

        $page->save();
    }

    protected function resolveContent(array $data): ?array
    {
        $blocks = Arr::get($data, 'data.blocksString');

        $blocks = is_string($blocks) ? Content::fromBlocksString($blocks) : null;
        $inputs = Arr::get($data, 'data.inputs') ?: [];

        if ($blocks) {
            return [
                'data' => [
                    'blocks' => $blocks->get(),
                    'inputs' => $inputs,
                ]
            ];
        }

        return null;
    }

    protected function resolveUrl(array $data): ?string
    {
        $match = collect(Arr::get($data, 'query'))
            ->where('@type', '@builder.io/core:Query')
            ->where('operator', 'is')
            ->firstWhere('property', 'urlPath');

        if (is_array($match) && ($path = Arr::get($match, 'value'))) {
            return '/'.ltrim($path, '/');
        }

        return null;
    }

    /**
     * Determine if the model is valid.
     */
    protected function isModelValid(string $model): bool
    {
        return collect(config('builder.localized_page_model_ids'))
            ->values()
            ->push(config('builder.page_model_id'))
            ->contains($model);
    }

    protected function resolveLocale(array $payload): ?string
    {
        $locale = Arr::get($payload, 'data.locale');

        if (is_string($locale)) {
            return $locale;
        }

        return null;
    }

    /**
     * Retrieve page locale for given model.
     * @deprecated
     */
    protected function resolveLocaleForModel(string $model): ?string
    {
        if ($model == config('builder.page_model_id')) {
            return null;
        }

        return collect(config('builder.localized_page_model_ids'))->flip()->get($model);
    }
}
