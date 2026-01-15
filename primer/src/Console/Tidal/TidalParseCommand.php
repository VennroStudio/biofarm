<?php

declare(strict_types=1);

namespace App\Console\Tidal;

use App\Components\Helper;
use App\Components\TidalGrab\RateLimitException;
use App\Components\TidalGrab\TidalGrab;
use App\Modules\Command\ArtistProblematic\Add\Handler as ArtistProblematicAddHandler;
use App\Modules\Command\Tidal\InactiveToken;
use App\Modules\Constant;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Query\Artists\FindArtistNeedTidalUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TidalParseCommand extends Command
{
    private const INTERVAL = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly TidalGrab $tidalGrab,
        private readonly InactiveToken\Handler $inactiveTokenHandler,
        private readonly FindArtistNeedTidalUpdate\Fetcher $findArtistNeedTidalUpdateFetcher,
        private readonly TidalAlbumsParser $tidalAlbumsParser,
        private readonly ArtistProblematicAddHandler $artistProblematicAddHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('tidal-parse')
            ->setDescription('Tidal parse command')
            ->addArgument('full', InputArgument::REQUIRED, 'full scan?')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?')
            ->addArgument('tokenId', InputArgument::OPTIONAL, 'token ID?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $countAttempts = 0;
        $excludedArtistIds = [];

        $isFullScan = (int)$input->getArgument('full') === 1;

        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $tokenId = null;
        if (null !== $input->getArgument('tokenId')) {
            $tokenId = (int)$input->getArgument('tokenId');
        }

        $output->writeln('[FULL SCAN: ' . ($isFullScan ? 'YES' : 'NO') . ', TOKEN: ' . ($tokenId ?: '-') . '] Parse Tidal started!');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return Command::FAILURE;
            }

            $this->em->clear();

            $accessToken = $this->getAccessToken($tokenId);
            $this->tidalGrab->setAccessToken($accessToken->getAccessToken());
            $this->tidalGrab->setCountryCode('US');
            $this->tidalGrab->setDelay(rand(5, 15));

            ++$countAttempts;
            $artist = $this->findArtistNeedTidalUpdateFetcher->fetch($isFullScan, $excludedArtistIds, $mod);

            if (null === $artist) {
                $countAttempts = 0;

                if (\count($excludedArtistIds) > 0) {
                    return Command::SUCCESS;
                }

                $output->writeln($this->time() . 'no artists (sleep...)');
                sleep(60);
                continue;
            }

            try {
                $this->tidalAlbumsParser->handle($artist->getId(), $isFullScan);

                $output->writeln($this->time() . '[id: ' . $artist->getId() . '] ' . $artist->getDescription());
                $countAttempts = 0;
            } catch (RateLimitException $e) {
                sleep(rand(20, 60));

                if ($countAttempts >= 10) {
                    $output->writeln($this->time() . '[id: ' . $artist->getId() . '] ' . $artist->getDescription() . ' — ' . $e->getMessage() . ' [' . $countAttempts . ' attempts]');

                    $excludedArtistIds[] = $artist->getId();
                    $countAttempts = 0;
                }

                continue;
            } catch (Throwable $e) {
                $this->inactiveAccessToken($accessToken->getId(), $e->getMessage());

                $excludedArtistIds[] = $artist->getId();

                $output->writeln($this->time() . $e->getMessage());

                $this->handleArtistNotFoundError($e->getMessage(), $artist->getId(), $artist->getLoName());

                sleep(5 * 60);

                continue;
            }

            if (!$isFullScan) {
                // if (self::INTERVAL < 60) {
                //     $output->writeln('<info>sleep ' . self::INTERVAL . ' sec...</info>');
                // } else {
                //     $output->writeln('<info>sleep ' . round(self::INTERVAL / 60, 2) . ' min...</info>');
                // }

                sleep(self::INTERVAL);
            }

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL_4) {
                break;
            }
        }

        return Command::SUCCESS;
    }

    private function getAccessToken(?int $tokenId): TidalToken
    {
        while (true) {
            if (null !== $tokenId) {
                $accessToken = $this->tidalTokenRepository->findById($tokenId);
            } else {
                $accessToken = $this->tidalTokenRepository->findFirstActive(TidalToken::TYPE_API);
            }

            if (null === $accessToken) {
                echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            try {
                if (
                    $accessToken->isExpired() &&
                    null !== $accessToken->getClientId() &&
                    null !== $accessToken->getClientSecret()
                ) {
                    $token = $this->tidalGrab->auth($accessToken->getClientId(), $accessToken->getClientSecret());

                    $accessToken->refresh($token->accessToken);
                    $this->tidalTokenRepository->add($accessToken);
                    $this->em->flush();
                }
            } catch (Throwable) {
                echo PHP_EOL . 'FAILED REFRESH ACCESS TOKEN!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            return $accessToken;
        }
    }

    private function inactiveAccessToken(int $id, string $error): void
    {
        $this->inactiveTokenHandler->handle(
            new InactiveToken\Command(
                id: $id,
                error: $error
            )
        );
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }

    private function handleArtistNotFoundError(string $errorMessage, int $artistId, string $artistName): void
    {
        if (preg_match('/NOT_FOUND:\s*Artist of a given \'id\'\s+(\d+)\s+not found/i', $errorMessage)) {
            try {
                $this->artistProblematicAddHandler->handle(
                    new \App\Modules\Command\ArtistProblematic\Add\Command(
                        artistId: $artistId,
                        artistName: $artistName,
                    )
                );
            } catch (Throwable) {
            }
        }
    }
}
