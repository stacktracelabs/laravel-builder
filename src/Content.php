<?php


namespace StackTrace\Builder;


use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Content
{
    /**
     * The Builder instance.
     */
    protected ?BuilderService $builder = null;

    public function __construct(
        protected array $blocks
    ) { }

    /**
     * Get the Builder instance.
     */
    protected function builder(): BuilderService
    {
        if ($this->builder) {
            return $this->builder;
        }

        return $this->builder = app(BuilderService::class);
    }

    /**
     * Retrieve content blocks.
     */
    public function render(): array
    {
        return $this->blocks;
    }

    /**
     * Resolve content symbols.
     */
    public function resolveSymbols(): static
    {
        $this->mapComponents(function (array $block) {
            if ($this->isSymbolBlock($block)) {
                $model = Arr::get($block, 'component.options.symbol.model');
                $id = Arr::get($block, 'component.options.symbol.entry');

                $symbol = BuilderContent::query()
                    ->published()
                    ->where('model', $model)
                    ->where('builder_id', $id)
                    ->first();

                if ($symbol) {
                    $content = $symbol->getContent();
                } else {
                    $content = [];
                }

                Arr::set($block, 'component.options.symbol.content', $content);

                return $block;
            }

            return $block;
        });

        return $this;
    }

    /**
     * Remove Pixel block.
     */
    public function removePixel(): static
    {
        // TODO: add support for filtering same as map
        $this->blocks = collect($this->blocks)->filter(fn (array $block) => ! $this->isPixelBlock($block))->all();

        return $this;
    }

    /**
     * Download images locally.
     */
    public function downloadImages(): static
    {
        // Download images from Image blocks.
        return $this->map(function (array $block) {
            if (! $this->isBuilderComponent($block, 'Image')) {
                return $block;
            }

            $url = Arr::get($block, 'component.options.image');

            if (! $url) {
                return $block;
            }

            $download = $this->downloadFile($url, "Image");

            Arr::set($block, 'component.options.image', $download);

            if ($srcset = Arr::get($block, 'component.options.srcset')) {
                $newSrcset = collect(explode(',', $srcset))->map(function ($it) {
                    $it = trim($it);

                    $url = trim(Str::before($it, " "));
                    $size = trim(Str::after($it, " "));

                    $newUrl = $this->downloadFile($url, "Image");

                    return "$newUrl {$size}";
                })->join(', ');

                Arr::set($block, 'component.options.srcset', $newSrcset);
            }

            return $block;
        });
    }

    /**
     * Download videos locally.
     */
    public function downloadVideos(): static
    {
        // Download videos from Video blocks.
        return $this->map(function (array $block) {
            if (! $this->isBuilderComponent($block, 'Video')) {
                return $block;
            }

            // Video
            if ($video = Arr::get($block, 'component.options.video')) {
                Arr::set($block, 'component.options.video', $this->downloadFile($video, "Video"));
            }

            // Poster
            if ($poster = Arr::get($block, 'component.options.posterImage')) {
                Arr::set($block, 'component.options.posterImage', $this->downloadFile($poster, "Video"));
            }

            return $block;
        });
    }

    /**
     * Download remote file locally. Public URL to downloaded file is returned.
     */
    protected function downloadFile(string $url, ?string $context = null): string
    {
        $hash = sha1($context ? $context . ':' . $url : $url);

        $path = $this->resolveFilePath($hash);

        if (! $this->storage()->exists($path)) {
            $stream = fopen("php://memory", "r+");
            fwrite($stream, file_get_contents($url));
            rewind($stream);
            $type = mime_content_type($stream);

            // Since Builder does not store file extensions, we have to just guess...
            if ($type && Str::contains($type, "image/svg")) {
                $path .= ".svg";
            } else if ($type == "image/png") {
                $path .= ".png";
            } else if ($type == "image/jpeg") {
                $path .= ".jpeg";
            } else if ($type == "video/mp4") {
                $path .= ".mp4";
            }

            rewind($stream);
            $this->storage()->put($path, $stream);
            fclose($stream);
        }

        return $this->storage()->url($path);
    }

    protected function storage(): Filesystem
    {
        return Storage::disk(config('builder.storage_disk'));
    }

    protected function resolveFilePath(string $name): string
    {
        if ($folder = config('builder.storage_folder')) {
            return $folder.'/'.$name;
        }

        return $name;
    }

    /**
     * Run a callback over each block in a tree.
     */
    public function map(Closure $callback): static
    {
        $this->blocks = $this->processBlockAndOptions($this->blocks, $callback);

        return $this;
    }

    /**
     * Traverse each block of the content.
     */
    public function traverse(Closure $callback): static
    {
        $this->processBlockAndOptions($this->blocks, $callback);

        return $this;
    }

    /**
     * Map each component.
     */
    public function mapComponents(Closure $callback): static
    {
        if (Arr::has($this->blocks, 'data.blocks')) {
            Arr::set($this->blocks, 'data.blocks', $this->walkComponents(Arr::get($this->blocks, 'data.blocks'), $callback));
        }

        return $this;
    }

    /**
     * Resolve array paths which actually exists in given data array based on the path template.
     */
    protected function resolveWildcards(array $data, string $path): array
    {
        $segments = explode('.', $path);

        $traverse = function ($current, $segments, $resolvedPath = '') use (&$traverse) {
            if (empty($segments)) {
                return [$resolvedPath];
            }

            $segment = array_shift($segments);
            $results = [];

            if ($segment === '*') {
                if (!is_array($current)) {
                    return [];
                }

                foreach ($current as $key => $value) {
                    $nextPath = $resolvedPath === '' ? $key : "$resolvedPath.$key";
                    $results = array_merge($results, $traverse($value, $segments, $nextPath));
                }
            } elseif (Str::contains($segment, '*')) {
                $pattern = '/^' . str_replace('*', '.*', preg_quote($segment, '/')) . '$/';

                if (!is_array($current)) {
                    return [];
                }

                foreach ($current as $key => $value) {
                    if (preg_match($pattern, $key)) {
                        $nextPath = $resolvedPath === '' ? $key : "$resolvedPath.$key";
                        $results = array_merge($results, $traverse($value, $segments, $nextPath));
                    }
                }
            } else {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return [];
                }

                $nextPath = $resolvedPath === '' ? $segment : "$resolvedPath.$segment";
                $results = array_merge($results, $traverse($current[$segment], $segments, $nextPath));
            }

            return $results;
        };

        return $traverse($data, $segments);
    }

    /**
     * Process components and its children.
     */
    protected function walkComponents($block, Closure $callback): mixed
    {
        if (Arr::isList($block) && is_array($block)) {
            return array_map(fn ($it) => $this->walkComponents($it, $callback), $block);
        } else {
            if ($this->isComponentBlock($block)) {
                $processed = $callback($block);

                if ($name = Arr::get($processed, 'component.name')) {
                    $childrenOptions = $this->builder()->getChildrenComponentOptions($name);

                    // Check for options which can contain children and walk through these components as well.
                    if (! empty($childrenOptions) && Arr::has($processed, 'component.options')) {
                        $options = Arr::get($processed, 'component.options');

                        foreach ($childrenOptions as $option) {
                            foreach ($this->resolveWildcards($options, $option) as $path) {
                                if ($value = Arr::get($options, $path)) {
                                    Arr::set($options, $path, $this->walkComponents($value, $callback));
                                }
                            }
                        }

                        Arr::set($processed, 'component.options', $options);
                    }
                }
            } else {
                $processed = $block;
            }

            if ($this->hasChildren($processed)) {
                $processed['children'] = $this->walkComponents($processed['children'], $callback);
            }

            return $processed;
        }
    }

    /**
     * Run given callback over each component block, returning mapped blocks.
     */
    protected function processBlockAndOptions($something, Closure $callback): mixed
    {
        if ($this->isComponentBlock($something)) {
            $processed = $callback($something);

            $options = Arr::get($processed, 'component.options');

            if ($options) {
                foreach ($options as $key => $value) {
                    $options[$key] = $this->processBlockAndOptions($value, $callback);
                }

                Arr::set($processed, 'component.options', $options);
            }

            return $processed;
        } else if (is_array($something)) {
            return collect($something)->map(fn ($it) => $this->processBlockAndOptions($it, $callback))->all();
        } else {
            return $something;
        }
    }

    /**
     * Determine if given structure is a component block.
     */
    public function isComponentBlock($block): bool
    {
        return is_array($block) && Arr::has($block, '@type') && Arr::has($block, 'component');
    }

    /**
     * Determine if the block has children.
     */
    public function hasChildren($block): bool
    {
        return is_array($block) && Arr::has($block, '@type') && Arr::has($block, 'children');
    }

    /**
     * Determine if given block is a pixel block.
     */
    public function isPixelBlock(array $block): bool
    {
        return Str::startsWith(Arr::get($block, 'id'), 'builder-pixel');
    }

    /**
     * Determine whether the block is a Symbol.
     */
    public function isSymbolBlock(array $block): bool
    {
        return $this->isBuilderComponent($block, 'Symbol');
    }

    /**
     * Determine if given block is built-in Builder block with given name.
     */
    public function isBuilderComponent(array $block, string $name): bool
    {
        return Arr::get($block, '@type') == '@builder.io/sdk:Element' && Arr::get($block, 'component.name') == $name;
    }

    public static function fromBlocks(array $blocks): static
    {
        return new static($blocks);
    }

    public static function fromBlocksString(string $blocks): static
    {
        return new static(json_decode($blocks, true));
    }
}
