<?php

declare(strict_types=1);

use App\Components\Integration\Credential\IntegrationCredentialStore;
use App\Modules\Ozon\OzonApi;
use App\Modules\Ozon\OzonConnection;
use App\Modules\Ozon\OzonWebhook;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    OzonApi::class => static fn (): OzonApi => new OzonApi(new Client()),
    OzonConnection::class => static fn (ContainerInterface $c): OzonConnection => new OzonConnection(
        $c->get(Connection::class),
        $c->get(IntegrationCredentialStore::class),
        $c->get(OzonApi::class),
        env('OZON_CLIENT_ID', ''),
        env('OZON_CLIENT_SECRET', ''),
        env('OZON_SELLER_ID', ''),
        env('OZON_REDIRECT_URI', ''),
    ),
    OzonWebhook::class => static fn (ContainerInterface $c): OzonWebhook => new OzonWebhook(
        $c->get(Connection::class),
        env('OZON_WEBHOOK_SECRET', ''),
        env('FRONTEND_URL', env('APP_URL', '')),
        env('OZON_SELLER_ID', ''),
    ),
];
