<?php

namespace Paragraph\LaravelNotifications\Storage;

use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LocalFiles
{
    const POSTFIX_SMS = 'sms';
    const POSTFIX_EMAIL = 'email';

    public string $workingDirectory = 'app/comms';

    public function storeMail(SentMessage $message, Notification $notification)
    {
        // Under no circumstances we want our special hook to be able to break the application
        // Anything bad that happens should be ignored
        try {
            $html = $message->getSymfonySentMessage()->getOriginalMessage()->getHtmlBody();
            $this->save($html, get_class($notification), $this::POSTFIX_EMAIL);
        } catch (\Throwable $e) {
            Log::error("Failed saving a rendered notification: {$e->getMessage()}");
        }
    }

    public function storeSms(array $message, Notification $notification)
    {
        try {
            $this->save($message['content'], get_class($notification), $this::POSTFIX_SMS);
        } catch (\Throwable $e) {
            Log::error("Failed saving a rendered notification: {$e->getMessage()}");
        }
    }

    public function save($contents, $name, $type)
    {
        $path = $this->filename($name, $type);

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path));
        }

        $start = microtime(true);
        $this->recordAHit($contents, $path);
        $end = microtime(true);

        if (config('app.debug')) {
            Log::info("Saved a new notification render for {$name} as {$path} in " . ($end - $start) . "s");
        }
    }

    public function cleanUp()
    {
        if (! file_exists(storage_path($this->workingDirectory))) {
            return;
        }

        $files = scandir(storage_path($this->workingDirectory));

        collect($files)
            ->filter(fn ($path) => preg_match('/\.hit$/', $path))
            ->each(fn ($path) => unlink(storage_path($this->workingDirectory.DIRECTORY_SEPARATOR.$path)));
    }

    protected function recordAHit($contents, $path)
    {
        if (! file_exists(storage_path($this->workingDirectory))) {
            mkdir(storage_path($this->workingDirectory));
        }

        if (file_exists($path)) {
            return;
        }

        $fp = fopen($path, 'w');

        if (function_exists('gzcompress')) {
            $contents = gzcompress($contents);
        }

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            file_put_contents($path, $contents);
        }

        fclose($fp);
    }

    protected function filename($name, $type)
    {
        $timestamp = time();
        $pid = getmypid();

        return storage_path("{$this->workingDirectory}/{$name}_{$type}_{$timestamp}_{$pid}.hit");
    }
}
