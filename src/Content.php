<?php


namespace StackTrace\Builder;


use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Content
{
    public function __construct(
        protected array $blocks
    ) { }

    public function get(): array
    {
        // Download images from Image blocks.
        $this->map(function (array $block) {
            if (! $this->isBuilderComponent($block, 'Image')) {
                return $block;
            }

            $url = Arr::get($block, 'component.options.image');

            if (! $url) {
                return $block;
            }

            $download = $this->downloadFile($url, "Image");

            Arr::set($block, 'component.options.image', $download);

            return $block;
        });

        // Download videos from Video blocks.
        $this->map(function (array $block) {
            if (! $this->isBuilderComponent($block, 'Video')) {
                return $block;
            }

            $url = Arr::get($block, 'component.options.video');

            if (! $url) {
                return $block;
            }

            $download = $this->downloadFile($url, "Video");

            Arr::set($block, 'component.options.video', $download);

            return $block;
        });

        return $this->blocks;
    }

    /**
     * Download remote file localy. Public URL to downloaded file is returned.
     */
    public function downloadFile(string $url, ?string $context = null): string
    {
        $hash = sha1($context ? $context . ':' . $url : $url);

        $path = $this->resolveFilePath($hash);

        if (! $this->storage()->exists($path)) {
            $contents = file_get_contents($url);

            $this->storage()->put($path, $contents);
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
    protected function map(Closure $callback): static
    {
        $this->blocks = $this->processBlock($this->blocks, $callback);

        return $this;
    }

    /**
     * Run given callback over each component block, returning mapped blocks.
     */
    protected function processBlock($something, Closure $callback): mixed
    {
        if ($this->isComponentBlock($something)) {
            $processed = $callback($something);

            $options = Arr::get($processed, 'component.options');

            if ($options) {
                foreach ($options as $key => $value) {
                    $options[$key] = $this->processBlock($value, $callback);
                }

                Arr::set($processed, 'component.options', $options);
            }

            return $processed;
        } else if (is_array($something)) {
            return collect($something)->map(fn ($it) => $this->processBlock($it, $callback))->all();
        } else {
            return $something;
        }
    }

    /**
     * Determine if given structure is a component block.
     */
    protected function isComponentBlock($block): bool
    {
        return is_array($block) && Arr::has($block, '@type') && Arr::has($block, 'component');
    }

    /**
     * Determine if given block is built-in Builder block with given name.
     */
    protected function isBuilderComponent(array $block, string $name): bool
    {
        return Arr::get($block, '@type') == '@builder.io/sdk:Element' && Arr::get($block, 'component.name') == $name;
    }

    public static function fromBlocksString(string $blocks): static
    {
        return new static(json_decode($blocks, true));
    }
}
