<?php

declare(strict_types=1);

use App\Console\Apple\AppleParseCommand;
use App\Console\Artists\PossibleArtistsCommand;
use App\Console\Loader\LoadAlbumsCommand;
use App\Console\Mapper\MapAlbumsCommand;
use App\Console\Other\FixAlbumCoverCommand;
use App\Console\Other\FixCommand;
use App\Console\Other\ImportCommand;
use App\Console\Other\NeuroCommand;
use App\Console\Other\SpotifyTokenCommand;
use App\Console\Other\SynchronizeCommand;
use App\Console\Playlists\PlaylistsCommand;
use App\Console\Playlists\PlaylistsImportCommand;
use App\Console\Playlists\PlaylistsImportCoverCommand;
use App\Console\Refresh\Artists\ArtistsCommand;
use App\Console\Refresh\RateArtists\RateArtistsParseCommand;
use App\Console\Refresh\RateArtists\RateCommand;
use App\Console\Refresh\SimilarAlbums\SimilarAlbumsParseCommand;
use App\Console\Refresh\Tracks\LyricsCommand;
use App\Console\Refresh\Tracks\SpotifyAdditionalParseCommand;
use App\Console\Spotify\SpotifyParseCommand;
use App\Console\Tidal\TidalParseCommand;
use App\Console\TrackProblematic\TrackProblematicAddSocialCommand;
use App\Console\TrackProblematic\TrackProblematicDeleteStatusCommand;
use App\Console\TrackProblematic\TrackProblematicResetCommand;
use App\Console\TrackProblematic\TrackProblematicSetStatusCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Psr\Container\ContainerInterface;

return [
    EntityManagerProvider::class => static fn (ContainerInterface $container): EntityManagerProvider => new SingleManagerProvider($container->get(EntityManagerInterface::class)),

    ValidateSchemaCommand::class => static fn (ContainerInterface $container): ValidateSchemaCommand => new ValidateSchemaCommand($container->get(EntityManagerProvider::class)),

    'config' => [
        'console' => [
            'commands' => [
                TrackProblematicResetCommand::class,
                TrackProblematicAddSocialCommand::class,
                TrackProblematicDeleteStatusCommand::class,
                TrackProblematicSetStatusCommand::class,

                ValidateSchemaCommand::class,
                ExecuteCommand::class,
                MigrateCommand::class,
                LatestCommand::class,
                ListCommand::class,
                StatusCommand::class,
                UpToDateCommand::class,

                SpotifyParseCommand::class,
                TidalParseCommand::class,
                AppleParseCommand::class,
                MapAlbumsCommand::class,
                LoadAlbumsCommand::class,

                SpotifyAdditionalParseCommand::class,
                ArtistsCommand::class,
                LyricsCommand::class,
                RateArtistsParseCommand::class,
                SimilarAlbumsParseCommand::class,
                RateCommand::class,

                PlaylistsCommand::class,
                PossibleArtistsCommand::class,
                PlaylistsImportCommand::class,
                PlaylistsImportCoverCommand::class,

                ImportCommand::class,
                NeuroCommand::class,
                FixCommand::class,
                FixAlbumCoverCommand::class,
                SynchronizeCommand::class,
                SpotifyTokenCommand::class,
            ],
        ],
    ],
];
