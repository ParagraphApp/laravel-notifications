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

## Commands

To automatically discover all Laravel Notification classes run:

```bash
$ php artisan paragraph:submit {namespace?}
```

By default the namespace is "App". This command will send the list of notification
classes as well as any collected data (rendered views, number of hits) to the Paragraph
dashboard via API.

## Creating an account

Sign up for a free account on https://paragraph.ph

## Tutorials

Detailed tutorials explaining how to manage your Laravel application in a better way are available in our blog https://paragraph.ph/blog
