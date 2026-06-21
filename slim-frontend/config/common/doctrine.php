<?php

declare(strict_types=1);

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function App\Components\env;

return [
    EntityManagerInterface::class => static function (ContainerInterface $container): EntityManagerInterface {
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{
         *     metadata_dirs: string[],
         *     dev_mode: bool,
         *     proxy_dir: string,
         *     cache_dir: string|null,
         *     types: array<string, class-string<Type>>,
         *     subscribers: string[],
         *     connection: array{
         *         driver: "pdo_pgsql",
         *         host: string,
         *         user: string,
         *         password: string,
         *         dbname: string,
         *         charset: string,
         *     }
         * } $settings
         */
        $settings = $container->get('config')['doctrine'];

        $metadataDirs = $settings['metadata_dirs'];

        if ($metadataDirs === []) {
            $metadataDirs = glob(__DIR__ . '/../../src/Modules/*/Entity') ?: [];
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: $metadataDirs,
            isDevMode: $settings['dev_mode'],
            proxyDir: $settings['proxy_dir'],
            cache: $settings['cache_dir'] !== null
                ? new FilesystemAdapter('', 0, $settings['cache_dir'])
                : new ArrayAdapter(),
        );

        $config->enableNativeLazyObjects(true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        foreach ($settings['types'] as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $eventManager = new EventManager();
        foreach ($settings['subscribers'] as $subscriberClass) {
            /** @var EventSubscriber $subscriber */
            $subscriber = $container->get($subscriberClass);
            $eventManager->addEventSubscriber($subscriber);
        }

        return new EntityManager(
            conn: DriverManager::getConnection($settings['connection'], $config),
            config: $config,
            eventManager: $eventManager,
        );
    },

    Connection::class => static function (ContainerInterface $container): Connection {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        return $entityManager->getConnection();
    },

    'config' => [
        'doctrine' => [
            'dev_mode'      => false,
            'cache_dir'     => __DIR__ . '/../../var/cache/doctrine/cache',
            'proxy_dir'     => __DIR__ . '/../../var/cache/doctrine/proxy',
            'metadata_dirs' => [],
            'types'         => [],
            'subscribers'   => [],
            'connection'    => [
                'driver'   => 'pdo_mysql',
                'host'     => env('DB_HOST'),
                'user'     => env('DB_USER'),
                'password' => env('DB_PASSWORD'),
                'dbname'   => env('DB_NAME'),
                'charset'  => env('DB_CHARSET', 'utf8mb4'),
            ],
        ],
    ],
];
