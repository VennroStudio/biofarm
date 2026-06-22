<?php

declare(strict_types=1);

namespace App\Components\Microservice;

use DuckBug\Duck;
use GuzzleHttp\Client;
use Symfony\Component\Translation\Translator;

readonly class IndexNowService extends MicroserviceClient
{
    private string $internalKey;

    public function __construct(
        Client $client,
        string $host,
        string $internalKey,
        Translator $translator
    ) {
        parent::__construct($client, rtrim($host, '/'), Duck::get(), $translator);
        $this->internalKey = $internalKey;
    }

    public function users(int $id): void
    {
        $this->ping('/users/' . $id);
    }

    public function communities(int $id): void
    {
        $this->ping('/communities/' . $id);
    }

    public function places(int $id): void
    {
        $this->ping('/places/' . $id);
    }

    public function events(int $id): void
    {
        $this->ping('/events/' . $id);
    }

    public function posts(int $id): void
    {
        $this->ping('/posts/' . $id);
    }

    private function ping(string $uri): void
    {
        $result = $this->post(
            uri: $uri,
            headers: [
                'X-Internal-Key' => $this->internalKey,
            ]
        );

        if ($result === null || (isset($result['error']) && $result['error'])) {
            $this->duck->error(
                message: 'IndexNowService -> ping() failed',
                context: [
                    'uri' => $uri,
                    'response' => $result,
                ]
            );
        }
    }
}
