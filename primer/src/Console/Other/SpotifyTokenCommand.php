<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Components\SpotifyGrab\SpotifyGrab;
use App\Components\SpotifyGrab\TokenException;
use App\Modules\Constant;
use App\Modules\Query\FindSpotifyTokenNeedRefresh;
use Doctrine\ORM\EntityManagerInterface;
use HeadlessChromium\Cookies\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SpotifyTokenCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindSpotifyTokenNeedRefresh\Fetcher $findSpotifyTokenNeedRefreshFetcher,
        private readonly SpotifyGrab $spotifyGrab
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('other:spotify-token')
            ->setDescription('Spotify token refresh command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Spotify token refresh started!');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('Connection closed!');
                return Command::SUCCESS;
            }

            $this->em->clear();

            $spotifyToken = $this->findSpotifyTokenNeedRefreshFetcher->fetch();

            if (null === $spotifyToken) {
                sleep(60);
                continue;
            }

            try {
                $accessToken = $this->spotifyGrab->authWhisperify(
                    $this->generateCookies($spotifyToken->getCookies() ?? '')
                );

                $spotifyToken->refresh($accessToken);
                $this->em->flush();

                $output->writeln($this->time() . '[ID: ' . $spotifyToken->getId() . '] ' . $spotifyToken->getComment());
            } catch (TokenException $e) {
                $output->writeln('[ID ' . $spotifyToken->getId() . '] ' . $e->getMessage());
                sleep(60);
                continue;
            } catch (Throwable $e) {
                $output->writeln('CRITICAL ERROR! [ID ' . $spotifyToken->getId() . '] ' . $e->getMessage());
                return Command::FAILURE;
            }

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return Command::SUCCESS;
    }

    /** @return Cookie[] */
    private function generateCookies(string $cookiesJson): array
    {
        $cookies = [];

        /**
         * @var array{
         *     name: string,
         *     value: string,
         *     domain: string,
         *     path: string,
         *     secure: bool,
         *     httpOnly: bool,
         * }[] $items
         */
        $items = json_decode($cookiesJson, true);

        foreach ($items as $item) {
            if (!isset($item['domain'])) {
                continue;
            }

            $cookies[] = Cookie::create(
                name: $item['name'],
                value: $item['value'],
                params: [
                    'domain'    => $item['domain'],
                    'path'      => $item['path'],
                    'secure'    => $item['secure'],
                    'httpOnly'  => $item['httpOnly'],
                ]
            );
        }

        return $cookies;
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i:s') . '] ';
    }
}
