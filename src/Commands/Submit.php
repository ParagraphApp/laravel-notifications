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

        $files = $this->findTemporaryFiles(storage_path($storage->workingDirectory));

        $notifications = collect($classes)
            ->filter(function ($class) {
                return is_subclass_of($class, Notification::class);
            })
            ->reject(fn ($c) => (new \ReflectionClass($c))->isAbstract())
            ->map(function ($class) use ($files) {
                $path = (new \ReflectionClass($class))->getFileName();
                $path = str_replace(base_path('/'), '', $path);

                return array_merge([
                    'name' => $class,
                    'description' => $path,
                ], $files->get($class, []));
            })
            ->map(function ($class) use ($storage) {
                if (empty($class['channels'])) {
                    return $class;
                }

                $class['channels'] = $class['channels']->map(function ($channel, $key) use ($storage, $class) {
                    $path = storage_path($storage->workingDirectory)."/{$class['class']}"."_{$key}.render.counter";
                    $hits = file_exists($path) ? file_get_contents($path) : 0;

                    return array_merge($channel, [
                        'hits' => $hits,
                    ]);
                });

                return $class;
            });

        $notifications->each(function ($notification) {
            $this->info('* '.$notification['name']);
            $this->line('Source file '.$notification['description']);

            if (isset($notification['channels'])) {
                $this->line(json_encode($notification['channels'], JSON_PRETTY_PRINT));
            }
        });

        if (! $notifications->count()) {
            $this->info("Nothing to send, aborting");
            return;
        }

        $this->info("Submitting {$notifications->count()} notifications via the API");

        $client = resolve(ApiClient::class);
        $client->submitNotifications($notifications->values()->toArray());

        $storage->resetCounters();
    }

    protected function findTemporaryFiles($folder)
    {
        if (! file_exists($folder)) {
            return collect([]);
        }

        $files = scandir(storage_path($folder));

        return collect($files)
            ->map(function ($file) use ($folder) {
                preg_match('/(?<class>.+)_(?<channel>.+)\.render$/', $file, $matches);

                if (! $matches) {
                    return;
                }

                return [
                    'class' => $matches['class'],
                    'channel' => $matches['channel'],
                    'render' => file_get_contents($folder.DIRECTORY_SEPARATOR.$file),
                ];
            })
            ->filter()
            ->groupBy('class')
            ->map(function ($class) {
                return [
                    'channels' => $class->mapWithKeys(fn ($g) => [$g['channel'] => ['render' => $g['render']]]),
                ];
            });
    }
}
