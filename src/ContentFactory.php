<?php


namespace StackTrace\Builder;


use Illuminate\Support\Arr;
use StackTrace\Builder\Facades\Builder;

class ContentFactory
{
    public function createFromWebhook(array $content): void
    {
        $payload = Arr::get($content, 'newValue');

        if (! is_array($payload)) {
            return;
        }

        $this->create($payload);
    }

    public function createFromContentAPI(array $content): void
    {
        $this->create($content);
    }

    protected function create(array $payload): void
    {
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

        if (! $content) {
            return;
        }

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
        $model = Builder::getModels()->firstWhere('id', $id);

        if ($model) {
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
                'blocks', 'state', 'url',
            ]);
        }

        return [];
    }

    protected function resolveContent(array $data): ?array
    {
        $data = Arr::get($data, 'data');

        if (Arr::has($data, 'blocksString') && $data['blocksString'] != null) {
            $blocks = Content::fromBlocksString($data['blocksString']);
        } else if (Arr::has($data, 'blocks')) {
            $blocks = Content::fromBlocks($data['blocks']);
        } else {
            $blocks = null;
        }

        $inputs = Arr::get($data, 'inputs') ?: [];

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
