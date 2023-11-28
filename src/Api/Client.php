<?php

namespace Paragraph\LaravelNotifications\Api;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected GuzzleClient $client;

    public function __construct()
    {
        $this->client = new GuzzleClient([
            'base_uri' => config('paragraph.api_url'),
            'headers' => [
                'Authorization' => 'Bearer '.config('paragraph.api_key'),
            ],
        ]);
    }

    public function submitNotifications(array $notifications)
    {
        $response = $this->client->post("repos/" . config('paragraph.project_id') . "/notifications/history", [
            'json' => $notifications,
        ]);
    }
}
