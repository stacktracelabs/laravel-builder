<?php


namespace StackTrace\Builder\Commands;


use Illuminate\Console\Command;
use StackTrace\Builder\BuilderContent;
use StackTrace\Builder\ContentFactory;

class RefreshCommand extends Command
{
    protected $signature = 'builder:refresh {content?}';

    protected $description = 'Refresh local content from Builder sources.';

    public function handle(ContentFactory $factory): int
    {
        if ($id = $this->argument('content')) {
            /** @var BuilderContent|null $content */
            $content = is_numeric($id)
                ? BuilderContent::query()->firstWhere('id', (int) $id)
                : null;

            if (! $content) {
                $this->error("The content with ID [$id] does not exist.");
                return self::FAILURE;
            }

            $factory->create($content->builder_data);

            $this->info("✔ Refreshed $content->model => {$content->name}");
            return self::SUCCESS;
        }

        BuilderContent::query()->eachById(function (BuilderContent $content) use ($factory) {
            $factory->create($content->builder_data);

            $this->info("✔ Refreshed $content->model => {$content->name}");
        });

        return self::SUCCESS;
    }
}
