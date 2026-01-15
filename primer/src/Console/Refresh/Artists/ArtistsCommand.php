<?php

declare(strict_types=1);

namespace App\Console\Refresh\Artists;

use App\Components\AppleGrab\AppleGrab;
use App\Modules\Constant;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Query\Artists\GetArtistSocials;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

class ArtistsCommand extends Command
{
    private const INTERVAL = 5 * 60;
    private AppleGrab $appleGrab;

    /** @throws Exception */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GetArtistSocials\Fetcher $socialsFetcher,
        private readonly AvatarLoader $avatarLoader,
    ) {
        parent::__construct();

        $this->appleGrab = new AppleGrab(
            teamId: env('APPLE_TEAM_ID'),
            keyId: env('APPLE_KEY_ID'),
            keyFile: env('APPLE_KEY_PATH'),
        );

        $this->appleGrab->setCountryCode('US');
        $this->appleGrab->setDelay(0);
    }

    protected function configure(): void
    {
        $this
            ->setName('refresh:artist')
            ->setDescription('Refresh artist command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $artists = $this->getArtists();

            if (\count($artists) === 0) {
                sleep(self::INTERVAL);
                continue;
            }

            foreach ($artists as $artist) {
                $output->writeln('<info>[' . $artist->getId() . '] ' . $artist->getDescription() . '</info>');

                try {
                    $this->refresh($artist);
                } catch (Throwable $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }

            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    /** @return Artist[] */
    private function getArtists(): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Artist::class, 'a')
            ->andWhere('a.checkedAt < a.appleCheckedAt OR a.checkedAt IS NULL')
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->setMaxResults(50);

        /** @var Artist[] $artists */
        $artists = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($artists as $artist) {
            $items[] = $artist;
        }

        return $items;
    }

    /** @throws Throwable */
    private function refresh(Artist $artist): void
    {
        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query($artist->getId())
        );

        $avatarUrl = null;

        foreach ($socials as $social) {
            if ($social->getType() === ArtistSocial::TYPE_APPLE) {
                $result = $this->appleGrab->getArtist($social->getIdByUrl());

                $social->setAvatar($result->avatar);
                $social->setName($result->name);
                $social->setInfo(
                    json_encode([
                        'type'          => $result->type,
                        'attributes'    => $result->attributes,
                    ])
                );

                if (null === $avatarUrl) {
                    $avatarUrl = $result->avatar;
                }
            }
        }

        if ($artist->getPriority() !== 1) {
            if (null !== $avatarUrl && $artist->getAvatar() !== $avatarUrl) {
                $this->avatarLoader->handle(
                    userId: $artist->getUserId(),
                    unionId: $artist->getUnionId(),
                    url: $avatarUrl
                );

                $artist->setAvatar($avatarUrl);
            }
        }

        $artist->setChecked();

        $this->em->flush();
    }
}
