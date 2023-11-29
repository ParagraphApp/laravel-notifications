<?php

namespace Paragraph\LaravelNotifications\Commands;

use Paragraph\LaravelNotifications\Services\CachingClassFinder;
use Paragraph\LaravelNotifications\Api\Client as ApiClient;
use Paragraph\LaravelNotifications\Storage\LocalFiles as Storage;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;

class Submit extends Command
{
    protected $signature = 'paragraph:submit {namespace?} {--ignore-cache}';

    protected $description = 'Auto-discover Notification classes, send data to API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $namespace = $this->argument('namespace') ?: 'App';
        $classes = CachingClassFinder::getClassesInNamespace($namespace, $this->option('ignore-cache') ?: false);
        $storage = resolve(Storage::class);

        $notifications = collect($classes)
            ->filter(function ($class) {
                return is_subclass_of($class, Notification::class);
            })
            ->reject(fn ($c) => (new \ReflectionClass($c))->isAbstract())
            ->map(function ($class) {
                $path = (new \ReflectionClass($class))->getFileName();
                $path = str_replace(base_path('/'), '', $path);

                return [
                    'name' => $class,
                    'description' => $path,
                ];
            })
            ->keyBy('name');

        $hits = $this
            ->findTemporaryFiles(storage_path($storage->workingDirectory))
            ->map(function($hit) use ($notifications) {
                $notification = $notifications->get($hit['notification']['name']);

                if (! $notification) {
                    return;
                }

                return array_merge($hit, compact('notification'));
            })
            ->filter();

        $hits->groupBy('name')->each(function ($group, $name) {
            $this->info('* '.$name);
            $this->line('Source file '.$group->first()['notification']['description']);
        });

        if (! $hits->count()) {
            $this->info("Nothing to send, aborting");
            return;
        }

        $this->info("Submitting {$hits->count()} notification records via the API");

        $client = resolve(ApiClient::class);
        $client->submitNotifications($hits->values()->toArray());

        $storage->cleanUp();
    }

    protected function findTemporaryFiles($folder)
    {
        if (! file_exists($folder)) {
            return collect([]);
        }

        $files = scandir($folder);

        return collect($files)
            ->filter(fn ($file) => preg_match('/(?<class>.+)_(?<timestamp>\d+\.\d+)_\d+\.hit$/', $file))
            ->map(fn ($file) => json_decode(file_get_contents($folder.DIRECTORY_SEPARATOR.$file), true))
            ->filter();
    }
}
