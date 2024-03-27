<?php


namespace StackTrace\Builder\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use StackTrace\Builder\BuilderService;
use StackTrace\Builder\ContentFactory;

class FetchCommand extends Command
{
    protected $signature = 'builder:fetch {model?}';

    protected $description = 'Fetch content from Builder.io and save it locally.';

    public function handle(ContentFactory $factory, BuilderService $builder): int
    {
        $model = $this->argument('model');

        $models = $model ? collect([$model]) : $builder->getModels()->pluck('name');

        $models->each(function (string $name, int $idx) use ($factory) {
            if ($idx > 0) {
                $this->info("");
            }

            $this->line("Fetching model [$name]");

            $this->fetchAllModelResults($name)->each(function (array $result) use ($factory) {
                $name = Arr::get($result, 'name');

                $factory->create($result);

                $this->info("✔ {$name}");
            });
        });

        return self::SUCCESS;
    }

    protected function fetchAllModelResults(string $name): Collection
    {
        $total = collect();

        $offset = 0;

        do {
            $results = $this->fetchModelResults($name, $offset);

            if ($results->isEmpty()) {
                break;
            }

            $total = $total->merge($results);

            $offset = $total->count();
        } while (true);

        return $total;
    }

    protected function fetchModelResults(string $name, int $offset = 0): Collection
    {
        $key = config('builder.api_key');

        $url = "https://cdn.builder.io/api/v3/content/{$name}?apiKey={$key}&limit=100";

        if ($offset > 0) {
            $url .= "&offset={$offset}";
        }

        return Http::get($url)->collect('results');
    }
}
