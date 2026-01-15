<?php

declare(strict_types=1);

namespace App\Console\Playlists;

use App\Modules\Command\PlaylistTranslate;
use App\Modules\Entity\Playlist\PlaylistRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslateRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class PlaylistsImportCoverCommand extends Command
{
    public function __construct(
        private readonly PlaylistTranslate\Update\Handler $playlistTranslateUpdateHandler,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistTranslateRepository $playlistTranslateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('playlists:import-covers')
            ->setDescription('Import playlists covers command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = __DIR__ . '/files/covers';

        $arr = [
            '-'                             => 0,
            // 'World'                      => 2,
            // 'Russia'                     => 3,
            // 'Ukraine'                    => 4,
            // 'Kazakhstan'                 => 28,
            // 'USA'                        => 6,
            // 'Sri Lanka'                  => 8,
            // 'Türkiye'                    => 7,
            // 'UK'                         => 20,
            // 'Indonesia'                  => 10,
            // 'Belarus'                    => 5,
            // 'Armenia'                    => 29,
            // 'Greece'                     => 21,
            // 'Bahrain'                    => 17,
            // 'Thailand'                   => 22,
            // 'Malta'                      => 23,
            // 'Czechia'                    => 36,
            // 'Saudi Arabia'               => 16,
            // 'United Arab Emirates'       => 18,
            // 'Uzbekistan'                 => 9,
            // 'Azerbaijan'                 => 30,
            // 'Hungary'                    => 41,
            // 'Moldova'                    => 39,
            // 'Latvia'                     => 34,
            // 'Poland'                     => 33,
            // 'Romania'                    => 35,
            // 'Mongolia'                   => 31,
            // 'Slovakia'                   => 37,
            // 'Slovenia'                   => 38,
            // 'Kyrgyzstan'                 => 32,
            // 'Finland'                    => 40,
            // 'Egypt'                      => 81,
            // 'Chile'                      => 82,
            // 'Cyprus'                     => 101,
            // 'Laos'                       => 110,
            // 'Ghana'                      => 45,
            // 'India'                      => 51,
            // 'Belize'                     => 98,
            // 'Gambia'                     => 105,
            // 'Niger'                      => 118,
        ];

        foreach ($arr as $name => $playlistId) {
            $this->refreshCovers($playlistId, $dir . '/' . $name, $name);
        }

        return 0;
    }

    private function refreshCovers(int $playlistId, string $path, string $name): void
    {
        echo PHP_EOL . 'ID: ' . $playlistId;

        $playlist = $this->playlistRepository->getById($playlistId);

        if (!str_contains(mb_strtolower($playlist->getName(), 'UTF-8'), mb_strtolower($name, 'UTF-8'))) {
            echo PHP_EOL . 'INVALID PLAYLIST NAME! ' . $name . ' :: ' . $playlist->getName();
            exit;
        }

        /** @var false|string[] $files */
        $files = scandir($path);
        if ($files === false) {
            exit('No files');
        }

        array_shift($files);
        array_shift($files);

        $i = 0;

        foreach ($files as $file) {
            ++$i;

            // if ($i < 103) {
            //     continue;
            // }

            $lang = str_replace('.jpg', '', $file);
            $lang = mb_strtolower($lang, 'UTF-8');

            if ($lang === 'zh-hans') {
                $lang = 'zh-Hans';
            } elseif ($lang === 'zh-hant') {
                $lang = 'zh-Hant';
            }

            $playlistTranslate = $this->playlistTranslateRepository->findByLang($playlist->getId(), $lang);
            if (null === $playlistTranslate) {
                exit('!' . $lang);
            }

            $filePath = $path . '/' . $file;

            echo PHP_EOL . '[' . $i . '] ' . $lang . ' - ' . $filePath;

            $this->playlistTranslateUpdateHandler->handle(
                new PlaylistTranslate\Update\Command(
                    playlistId: $playlist->getId(),
                    translateId: $playlistTranslate->getId(),
                    name: $playlistTranslate->getName(),
                    description: $playlistTranslate->getDescription() ?? '',
                    filePath: $filePath
                )
            );
        }
    }
}
