<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Tracks\Conflict;

use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\Track\Track;
use Doctrine\DBAL\Connection;

final readonly class Fetcher
{
    public function __construct(
        private Connection $connection
    ) {}

    public function fetch(Query $query): array
    {
        $sql = $this->connection->createQueryBuilder()
            ->select([
                't.id',
                't.is_approved', 't.is_reissued', 't.lo_track_id',
                't.spotify_id', 't.spotify_name', 't.spotify_disk_number', 't.spotify_track_number', 't.spotify_isrc', 't.spotify_artists',
                't.spotify_is_deleted',
                'at.id AS apple_id', 'at.name AS apple_name', 'at.disk_number AS apple_disk_number', 'at.track_number AS apple_track_number', 'at.isrc AS apple_isrc', 'at.artists AS apple_artists',
                'at.is_deleted AS apple_is_deleted',
                'tt.tidal_id', 'tt.name', 'tt.disk_number', 'tt.track_number', 'tt.isrc', 'tt.artists',
                'tt.is_deleted',
            ])
            ->from(Track::DB_NAME, 't')
            ->innerJoin('t', TidalTrack::DB_NAME, 'tt', 'tt.id = t.tidal_track_id')
            ->leftJoin('t', AppleTrack::DB_NAME, 'at', 'at.id = t.apple_track_id');

        if (null !== $query->status) {
            $sql
                ->andWhere('t.is_approved = :is_approved')
                ->setParameter('is_approved', $query->status);
        }

        if (null !== $query->albumId) {
            $sql
                ->andWhere('t.album_id = :albumId')
                ->setParameter('albumId', $query->albumId);
        }

        $result = $sql
            ->orderBy('t.spotify_disk_number', 'ASC')
            ->orderBy('t.spotify_track_number', 'ASC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     is_approved: int,
         *     is_reissued: int,
         *     lo_track_id: ?int,
         *     spotify_id: string,
         *     spotify_name: string,
         *     spotify_disk_number: int,
         *     spotify_track_number: int,
         *     spotify_isrc: string,
         *     spotify_artists: string,
         *     spotify_is_deleted: int,
         *     apple_id: string,
         *     apple_name: string,
         *     apple_disk_number: int,
         *     apple_track_number: int,
         *     apple_isrc: string,
         *     apple_artists: string,
         *     apple_is_deleted: int,
         *     tidal_id: int,
         *     name: string,
         *     disk_number: int,
         *     track_number: int,
         *     isrc: string,
         *     artists: string,
         *     is_deleted: int,
         * }[] $rows
         */
        $rows = $result->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $spotifyArtists = [];
            $tidalArtists = [];

            /** @var array{name: string}[] $spotifyArtistsTemp */
            $spotifyArtistsTemp = json_decode($row['spotify_artists'] ?? '[]', true) ?? [];
            foreach ($spotifyArtistsTemp as $spotifyArtist) {
                $spotifyArtists[] = $spotifyArtist['name'];
            }

            /** @var array{name: string}[] $tidalArtistsTemp */
            $tidalArtistsTemp = json_decode($row['artists'] ?? '[]', true) ?? [];
            foreach ($tidalArtistsTemp as $tidalArtist) {
                $tidalArtists[] = $tidalArtist['name'];
            }

            $result[] = [
                'id'                    => $row['id'],
                'is_approved'           => $row['is_approved'],
                'is_reissued'           => $row['is_reissued'],
                'lo_track_id'           => $row['lo_track_id'],
                'spotify_id'            => $row['spotify_id'],
                'spotify_name'          => $row['spotify_name'],
                'spotify_disk_number'   => $row['spotify_disk_number'],
                'spotify_track_number'  => $row['spotify_track_number'],
                'spotify_isrc'          => $row['spotify_isrc'],
                'spotify_artists'       => implode(', ', $spotifyArtists),
                'spotify_is_deleted'    => (bool)$row['spotify_is_deleted'],
                'apple_id'              => $row['apple_id'],
                'apple_name'            => $row['apple_name'],
                'apple_disk_number'     => $row['apple_disk_number'],
                'apple_track_number'    => $row['apple_track_number'],
                'apple_isrc'            => $row['apple_isrc'],
                'apple_artists'         => $row['apple_artists'],
                'apple_is_deleted'      => (bool)$row['apple_is_deleted'],
                'tidal_id'              => $row['tidal_id'],
                'tidal_name'            => $row['name'],
                'tidal_disk_number'     => $row['disk_number'],
                'tidal_track_number'    => $row['track_number'],
                'tidal_isrc'            => $row['isrc'],
                'tidal_artists'         => implode(', ', $tidalArtists),
                'tidal_is_deleted'      => (bool)$row['is_deleted'],
            ];
        }

        return $result;
    }
}
