<?php

namespace Paragraph\LaravelNotifications\Providers;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\ServiceProvider;
use Paragraph\LaravelNotifications\Channels\ParagraphMailChannel;
use Paragraph\LaravelNotifications\Commands\Submit;

class ParagraphServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config/paragraph.php', 'paragraph');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Submit::class
            ]);
        }
    }

    public function register()
    {
        $this->app->bind(MailChannel::class, ParagraphMailChannel::class);
    }
}
