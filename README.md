# laravel-notifications
Paragraph official Laravel Notifications package 

## Installation

The easiest way to install Paragraph Laravel package is by using Composer:

```bash
$ composer require paragraph/laravel-notifications
```

It's almost ready, just copy & paste your Paragraph project ID and API key in your .env file:

```bash
PARAGRAPH_PROJECT_ID=XXX
PARAGRAPH_API_KEY=YYY
```

To get your API key and project ID simply create a new project on Paragraph:
https://paragraph.ph/repos/import

## Commands

To automatically discover all Laravel Notification classes run:

```bash
$ php artisan paragraph:submit [namespace]
```

By default, the namespace is "App". This command will send the list of notification
classes as well as any collected data (rendered views, number of hits) to the Paragraph
dashboard via API.

Class auto-discovery in PHP is an expensive process and takes quite some time,
because of this we cache the list of classes using Laravel's Storage facade using the 
'file' driver. If you just added a new notification class and want to make sure it's 
discovered you can pass an extra argument:

```bash
$ php artisan paragraph:submit --ignore-cache
```

## Creating an account

Sign up for a free account on https://paragraph.ph

## Tutorials

Detailed tutorials explaining how to manage your Laravel application in a better way are available in our blog https://paragraph.ph/blog
