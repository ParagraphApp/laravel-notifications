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
            $this->save($html, $notification, $this::POSTFIX_EMAIL);
        } catch (\Throwable $e) {
            Log::error("Failed saving a rendered notification: {$e->getMessage()}");
        }
    }

    public function storeSms(array $message, Notification $notification)
    {
        try {
            $this->save($message['content'], $notification, $this::POSTFIX_SMS);
        } catch (\Throwable $e) {
            Log::error("Failed saving a rendered notification: {$e->getMessage()}");
        }
    }

    protected function save($contents, Notification $notification, $type)
    {
        $path = $this->filename($notification, $type);

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path));
        }

        $this->recordAHit($path);

        if (file_exists($path)) {
            return;
        }

        file_put_contents($path, $contents);

        Log::info('Saved a new notification render for '.get_class($notification)." as {$path}");
    }

    protected function recordAHit($path)
    {
        $counterFile = "{$path}.counter";

        if (! file_exists($counterFile)) {
            touch($counterFile);
            $currentNumber = 0;
        } else {
            $currentNumber = file_get_contents($counterFile);
        }

        $fp = fopen($counterFile, 'r+');

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            file_put_contents($counterFile, $currentNumber + 1);
            flock($fp, LOCK_UN);
        }

        fclose($fp);
    }

    protected function filename(Notification $notification, $type)
    {
        $key = get_class($notification);

        return storage_path("{$this->workingDirectory}/{$key}_{$type}.render");
    }
}
