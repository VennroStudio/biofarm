<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LocalizeProductImagesCommand extends Command
{
    private const string PUBLIC_PATH = '/uploads/products/imported';

    private readonly string $publicDir;

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
        $this->publicDir = dirname(__DIR__, 2) . '/public';
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setName('media:localize-product-images')
            ->setDescription('Download external product images to public uploads and rewrite product image paths.');
    }

    /**
     * @throws \Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, product_id, path FROM product_images WHERE path LIKE 'http%' ORDER BY id ASC"
        );

        if ($rows === []) {
            $output->writeln('<info>External product images not found.</info>');

            return Command::SUCCESS;
        }

        $targetDir = $this->publicDir . self::PUBLIC_PATH;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create directory: ' . $targetDir);
        }

        $client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent' => 'Biofarm image localizer',
            ],
        ]);

        $rewrites = [];
        foreach ($rows as $row) {
            $source = trim((string)$row['path']);
            $localPath = $this->download($client, $source, $targetDir);
            if ($localPath === null) {
                $output->writeln('<comment>Skipped: ' . $source . '</comment>');
                continue;
            }

            $metadata = $this->metadata($this->publicDir . $localPath);
            $this->connection->update('product_images', [
                'path'      => $localPath,
                'width'     => $metadata['width'],
                'height'    => $metadata['height'],
                'mime_type' => $metadata['mime_type'],
                'size'      => $metadata['size'],
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], ['id' => (int)$row['id']]);

            $rewrites[$source] = $localPath;
            $output->writeln('<info>Localized:</info> ' . $source . ' -> ' . $localPath);
        }

        if ($rewrites !== []) {
            $this->rewriteProducts($rewrites);
        }

        $output->writeln('<info>Done. Localized ' . \count($rewrites) . ' unique product image URLs.</info>');

        return Command::SUCCESS;
    }

    /**
     * @throws GuzzleException
     */
    private function download(Client $client, string $source, string $targetDir): ?string
    {
        if ($source === '' || (!str_starts_with($source, 'http://') && !str_starts_with($source, 'https://'))) {
            return null;
        }

        $extension = $this->extensionFromUrl($source);
        $target = $targetDir . '/' . sha1($source) . '.' . $extension;
        $publicPath = self::PUBLIC_PATH . '/' . basename($target);

        if (is_file($target) && filesize($target) > 0) {
            return $publicPath;
        }

        $response = $client->request('GET', $source);
        if ($response->getStatusCode() >= 400) {
            return null;
        }

        file_put_contents($target, (string)$response->getBody());

        return is_file($target) && filesize($target) > 0 ? $publicPath : null;
    }

    private function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = \is_string($path) ? strtolower((string)pathinfo($path, PATHINFO_EXTENSION)) : '';

        return \in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true) ? $extension : 'jpg';
    }

    /**
     * @return array{width: int|null, height: int|null, mime_type: string|null, size: int|null}
     */
    private function metadata(string $path): array
    {
        $size = @getimagesize($path);

        return [
            'width'     => \is_array($size) ? (int)$size[0] : null,
            'height'    => \is_array($size) ? (int)$size[1] : null,
            'mime_type' => \is_array($size) && isset($size['mime']) ? (string)$size['mime'] : null,
            'size'      => is_file($path) ? (int)filesize($path) : null,
        ];
    }

    /**
     * @param array<string, string> $rewrites
     * @throws \JsonException
     */
    private function rewriteProducts(array $rewrites): void
    {
        foreach ($rewrites as $source => $target) {
            $this->connection->executeStatement(
                'UPDATE products SET image = :target, updated_at = UTC_TIMESTAMP() WHERE image = :source',
                ['target' => $target, 'source' => $source],
            );
        }

        $products = $this->connection->fetchAllAssociative(
            "SELECT id, images FROM products WHERE images IS NOT NULL AND images LIKE '%http%'"
        );

        foreach ($products as $product) {
            $images = json_decode((string)$product['images'], true, flags: JSON_THROW_ON_ERROR);
            if (!\is_array($images)) {
                continue;
            }

            $changed = false;
            foreach ($images as $index => $image) {
                if (!\is_string($image) || !isset($rewrites[$image])) {
                    continue;
                }

                $images[$index] = $rewrites[$image];
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $this->connection->update('products', [
                'images'     => json_encode(array_values($images), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], ['id' => (int)$product['id']]);
        }
    }
}
