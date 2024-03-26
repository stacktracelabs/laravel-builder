<?php


namespace StackTrace\Builder;


class BuilderEditor
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $url,
        public readonly string $model,
        public readonly string $locale
    ) { }
}
