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

        $type = $this->resolveType($payload);

        if (! $type) {
            return;
        }

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

        $locale = $this->resolveLocale($payload);
        $url = $this->resolveUrl($payload);
        $content = $this->resolveContent($payload);
        $isPublished = Arr::get($payload, 'published') == 'published';
        $title = Arr::get($payload, 'data.title');
        $fields = $this->resolveFields($payload);
        $name = Arr::get($payload, 'name');

        /** @var \StackTrace\Builder\BuilderContent $page */
        $page = BuilderContent::query()->firstWhere('builder_id', $id) ?: new BuilderContent([
            'builder_id' => $id,
        ]);

        $page->fill([
            'name' => $name,
            'type' => $type,
            'model' => $modelName,
            'path' => $url,
            'content' => $content,
            'builder_data' => $payload,
            'locale' => $locale,
            'title' => $title,
            'fields' => $fields,
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

    protected function resolveType(array $payload): ?ContentType
    {
        if (Arr::get($payload, 'meta.kind') == 'page') {
            return ContentType::Page;
        } else if (Arr::get($payload, 'meta.kind') == 'component') {
            return ContentType::Section;
        }

        return null;
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

    protected function resolveFields(array $payload): array
    {
        $data = Arr::get($payload, 'data');

        if ($data) {
            return Arr::except($data, [
                'blocksString', 'locale', 'themeId', 'title', 'inputs',
            ]);
        }

        return [];
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

    protected function resolveLocale(array $payload): ?string
    {
        $locale = Arr::get($payload, 'data.locale');

        if (is_string($locale)) {
            return $locale;
        }

        return null;
    }

}
