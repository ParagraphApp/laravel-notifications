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

        $files = file_exists(storage_path($storage->workingDirectory)) ? scandir(storage_path($storage->workingDirectory)) : [];
        $files = collect($files)
            ->map(function ($path) use ($storage) {
                preg_match('/(?<class>.+)_(?<channel>.+)\.render$/', $path, $matches);

                if (! $matches) {
                    return;
                }

                return [
                    'class' => $matches['class'],
                    'channel' => $matches['channel'],
                    'render' => file_get_contents(storage_path($storage->workingDirectory).DIRECTORY_SEPARATOR.$path),
                ];
            })
            ->filter()
            ->groupBy('class')
            ->map(function ($class) {
                return [
                    'channels' => $class->mapWithKeys(fn ($g) => [$g['channel'] => ['render' => $g['render']]]),
                ];
            });

        $notifications = collect($classes)
            ->filter(function ($class) {
                return is_subclass_of($class, Notification::class);
            })
            ->reject(fn ($c) => (new \ReflectionClass($c))->isAbstract())
            ->map(function ($class) use ($files) {
                $path = (new \ReflectionClass($class))->getFileName();
                $path = str_replace(base_path('/'), '', $path);

                return array_merge([
                    'class' => $class,
                    'class_file' => $path,
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
            $this->info('* '.$notification['class']);
            $this->line('Source file '.$notification['class_file']);

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

        $files = scandir(storage_path($storage->workingDirectory));

        collect($files)
            ->filter(fn ($path) => preg_match('/\.counter$/', $path))
            ->unique()
            ->each(fn ($path) => unlink(storage_path($storage->workingDirectory.DIRECTORY_SEPARATOR.$path)));
    }
}
