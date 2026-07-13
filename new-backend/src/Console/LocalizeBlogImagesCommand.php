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

final class LocalizeBlogImagesCommand extends Command
{
    private const string PUBLIC_PATH = '/uploads/blog/imported';

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
            ->setName('media:localize-blog-images')
            ->setDescription('Download external blog post images to public uploads and rewrite post image paths.');
    }

    /**
     * @throws \Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, image FROM blog_posts WHERE image LIKE 'http%' ORDER BY id ASC"
        );

        if ($rows === []) {
            $output->writeln('<info>External blog images not found.</info>');

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

        $localized = 0;
        foreach ($rows as $row) {
            $source = trim((string)$row['image']);
            $localPath = $this->download($client, $source, $targetDir);
            if ($localPath === null) {
                $output->writeln('<comment>Skipped: ' . $source . '</comment>');
                continue;
            }

            $this->connection->update('blog_posts', [
                'image'      => $localPath,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ], ['id' => (int)$row['id']]);

            ++$localized;
            $output->writeln('<info>Localized:</info> ' . $source . ' -> ' . $localPath);
        }

        $output->writeln('<info>Done. Localized ' . $localized . ' blog image URLs.</info>');

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

        $hash = sha1($source);
        $existing = glob($targetDir . '/' . $hash . '.*');
        if (\is_array($existing) && isset($existing[0]) && is_file($existing[0]) && filesize($existing[0]) > 0) {
            return self::PUBLIC_PATH . '/' . basename($existing[0]);
        }

        $response = $client->request('GET', $source);
        if ($response->getStatusCode() >= 400) {
            return null;
        }

        $contentType = (string)($response->getHeaderLine('Content-Type') ?: '');
        $extension = $this->extensionFromUrl($source) ?? $this->extensionFromMime($contentType) ?? 'jpg';
        $target = $targetDir . '/' . $hash . '.' . $extension;
        file_put_contents($target, (string)$response->getBody());

        return is_file($target) && filesize($target) > 0 ? self::PUBLIC_PATH . '/' . basename($target) : null;
    }

    private function extensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = \is_string($path) ? strtolower((string)pathinfo($path, PATHINFO_EXTENSION)) : '';

        return \in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true) ? $extension : null;
    }

    private function extensionFromMime(string $mime): ?string
    {
        $mime = strtolower(strtok($mime, ';') ?: '');

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif',
            default      => null,
        };
    }
}
