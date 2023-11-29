<?php

namespace Paragraph\LaravelNotifications\Channels;

use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Channels\MailChannel as LaravelMailChannel;
use Illuminate\Notifications\Notification;
use Paragraph\LaravelNotifications\Storage\LocalFiles as Storage;

class ParagraphMailChannel extends LaravelMailChannel
{
    public function send($notifiable, Notification $notification)
    {
        $sent = parent::send($notifiable, $notification);

        if (! $sent instanceof SentMessage) {
            return $sent;
        }

        resolve(Storage::class)->storeMail($sent, $notification, $notifiable);

        return $sent;
    }
}
