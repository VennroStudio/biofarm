<?php

declare(strict_types=1);

namespace App\Console\Playlists;

use App\Modules\Command\Playlist;
use App\Modules\Command\PlaylistTranslate;
use App\Modules\Entity\Playlist\PlaylistRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslateRepository;
use GuzzleHttp\Exception\GuzzleException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class PlaylistsImportCommand extends Command
{
    public function __construct(
        private readonly Playlist\Create\Handler $playlistCreateHandler,
        private readonly Playlist\Update\Handler $playlistUpdateHandler,
        private readonly PlaylistTranslate\Create\Handler $playlistTranslateCreateHandler,
        private readonly PlaylistTranslate\Update\Handler $playlistTranslateUpdateHandler,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistTranslateRepository $playlistTranslateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('playlists:import')
            ->setDescription('Import playlists command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nameRows = $this->readFile(__DIR__ . '/files/Top100Name.xlsx');
        $descriptionRows = $this->readFile(__DIR__ . '/files/Top100Description.xlsx');

        $data = [];
        $languages = [];

        /**
         * @var int $col
         * @var string|null $lang
         */
        foreach ($nameRows[0] as $col => $lang) {
            if ($lang === '' || $lang === null) {
                continue;
            }

            $languages[$col] = $lang;
        }

        /**
         * @var int $i
         * @var array $row
         */
        foreach ($nameRows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            /** @var array<int, string|null> $row */
            foreach ($row as $col => $translate) {
                if ($col === 0) {
                    continue;
                }

                if ($translate === '' || $translate === null) {
                    continue;
                }

                $lang = $languages[$col] ?? null;
                $id = $row[0];

                if (null === $lang || null === $id) {
                    continue;
                }

                $data[$id][$lang] = [
                    'name'          => trim(str_replace('  ', ' ', $translate)),
                    'description'   => '',
                ];
            }
        }

        /**
         * @var int $i
         * @var array $row
         */
        foreach ($descriptionRows as $i => $row) {
            if ($i === 0) {
                continue;
            }

            /** @var array<int, string|null> $row */
            foreach ($row as $col => $translate) {
                if ($col === 0) {
                    continue;
                }

                if ($translate === '' || $translate === null) {
                    continue;
                }

                $lang = $languages[$col] ?? null;
                $id = $row[0];

                if (null === $lang || null === $id) {
                    continue;
                }

                $data[$id][$lang]['description'] = trim(str_replace('  ', ' ', $translate));
            }
        }

        foreach ($data as $playlistUrl => $translations) {
            $output->writeln('<info>Playlist: ' . $playlistUrl . '</info>');

            $this->refreshPlaylist($playlistUrl, $translations);
        }

        $output->writeln('<info>Total: ' . \count($data) . '</info>');

        return 0;
    }

    /**
     * @param array<non-empty-string, array{name: string, description: string}> $translations
     * @throws GuzzleException|Throwable
     */
    private function refreshPlaylist(string $playlistUrl, array $translations): void
    {
        $playlist = $this->playlistRepository->findByUrl($playlistUrl);

        if (null === $playlist) {
            $playlist = $this->playlistCreateHandler->handle(
                new Playlist\Create\Command(
                    unionId: 5,
                    userId: 1,
                    name: $translations['en']['name'],
                    url: $playlistUrl,
                    isFollowed: true
                )
            );
        } else {
            $this->playlistUpdateHandler->handle(
                new Playlist\Update\Command(
                    playlistId: $playlist->getId(),
                    name: $translations['en']['name'],
                    isFollowed: true
                )
            );
        }

        foreach ($translations as $lang => $translation) {
            $playlistTranslate = $this->playlistTranslateRepository->findByLang($playlist->getId(), $lang);

            if (null === $playlistTranslate) {
                $this->playlistTranslateCreateHandler->handle(
                    new PlaylistTranslate\Create\Command(
                        playlistId: $playlist->getId(),
                        lang: $lang,
                        name: $translation['name'],
                        description: $translation['description'],
                        filePath: null
                    )
                );
            } else {
                $this->playlistTranslateUpdateHandler->handle(
                    new PlaylistTranslate\Update\Command(
                        playlistId: $playlist->getId(),
                        translateId: $playlistTranslate->getId(),
                        name: $translation['name'],
                        description: $translation['description'],
                        filePath: null
                    )
                );
            }
        }
    }

    private function readFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        return $worksheet->toArray();
    }
}
