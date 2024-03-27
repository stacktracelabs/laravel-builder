<?php


namespace StackTrace\Builder\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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

        $models->each(function (string $name, int $idx) use ($factory, $builder) {
            if ($idx > 0) {
                $this->info("");
            }

            $this->line("Fetching model [$name]");

            $builder->getContentEntriesByModelName($name)->each(function (array $result) use ($factory) {
                $name = Arr::get($result, 'name');

                $factory->create($result);

                $this->info("✔ {$name}");
            });
        });

        return self::SUCCESS;
    }
}
