<?php

declare(strict_types=1);

namespace App\Console;

use App\Components\Image\ImageVariantGenerator;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateImageVariantsCommand extends Command
{
    private const array EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    public function __construct(
        private readonly ImageVariantGenerator $generator,
        private readonly string $uploadsPath,
        private readonly string $publicRoot,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setName('media:generate-image-variants')
            ->setDescription('Generate local WebP and AVIF variants for uploaded images.')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Regenerate existing variants.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir($this->uploadsPath)) {
            $output->writeln('<comment>Uploads directory does not exist.</comment>');

            return Command::SUCCESS;
        }

        $overwrite = (bool)$input->getOption('overwrite');
        $processed = 0;
        $created = 0;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->uploadsPath));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!\in_array($extension, self::EXTENSIONS, true)) {
                continue;
            }

            $processed++;
            $publicPath = $this->publicPath($file->getPathname());
            $created += \count($this->generator->generate($publicPath, $overwrite));
        }

        $output->writeln(\sprintf('<info>Processed %d images, created %d variants.</info>', $processed, $created));

        return Command::SUCCESS;
    }

    private function publicPath(string $absolutePath): string
    {
        $relativePath = ltrim(substr($absolutePath, \strlen(rtrim($this->publicRoot, '/'))), '/');

        return '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }
}
