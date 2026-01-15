<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Albums\Conflict;

use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
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
                'a.id', 'a.is_approved', 'a.is_reissued',
                'a.spotify_id', 'a.spotify_name', 'a.spotify_total_tracks', 'a.spotify_type', 'a.spotify_released_at', 'a.spotify_upc', 'a.spotify_artists',
                'a.spotify_is_deleted',
                'a.all_tracks_mapped',
                'apple.id AS a_id', 'apple.apple_id', 'apple.name AS apple_name', 'apple.total_tracks AS apple_total_tracks', 'apple.is_compilation AS apple_is_compilation', 'apple.is_single AS apple_is_single', 'apple.released_at AS apple_released_at', 'apple.upc AS apple_upc', 'apple.artists AS apple_artists',
                'apple.is_deleted AS apple_is_deleted',
                'ta.id AS t_id', 'ta.tidal_id', 'ta.name', 'ta.total_tracks', 'ta.type', 'ta.released_at', 'ta.barcode_id', 'ta.artists',
                'ta.is_deleted',
                '(SELECT COUNT(*) FROM tracks WHERE tidal_track_id IS NOT NULL && album_id = a.id) as merged_tracks',
            ])
            ->from(Album::DB_NAME, 'a')
            ->innerJoin('a', TidalAlbum::DB_NAME, 'ta', 'ta.id = a.tidal_album_id')
            ->leftJoin('a', AppleAlbum::DB_NAME, 'apple', 'apple.id = a.apple_album_id');

        if ($query->status === 0) {
            $sql
                ->andWhere('a.is_approved = :is_approved')
                ->setParameter('is_approved', 0);
        } elseif ($query->status === 1) {
            $sql
                ->andWhere('a.is_approved = :is_approved')
                ->setParameter('is_approved', 1);
        } elseif ($query->status === 2) {
            $sql
                ->andWhere('a.all_tracks_mapped = :all_tracks_mapped')
                ->setParameter('all_tracks_mapped', 0);
        }

        if (null !== $query->artistId) {
            $sql
                ->addSelect(['aa.is_loaded'])
                ->leftJoin('a', AlbumArtist::DB_NAME, 'aa', 'aa.album_id = a.id AND aa.artist_id = :artistId')
                ->andWhere('a.id IN(SELECT album_id FROM album_artists WHERE artist_id = :artistId)')
                ->setParameter('artistId', $query->artistId);
        } else {
            $sql->addSelect(['(0) AS is_loaded']);
        }

        $result = $sql
            ->orderBy('a.spotify_released_at', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(10000)
            ->executeQuery();

        /** @var array{
         *     id: int,
         *     t_id: int,
         *     a_id: int,
         *     is_approved: int,
         *     is_reissued: int,
         *     spotify_id: string,
         *     spotify_name: string,
         *     spotify_total_tracks: int,
         *     spotify_type: string,
         *     spotify_released_at: int,
         *     spotify_upc: string,
         *     spotify_artists: string,
         *     spotify_is_deleted: int,
         *     apple_id: string,
         *     apple_name: string,
         *     apple_total_tracks: int,
         *     apple_is_compilation: int,
         *     apple_is_single: int,
         *     apple_released_at: int,
         *     apple_upc: string,
         *     apple_artists: string,
         *     apple_is_deleted: int,
         *     tidal_id: int,
         *     name: string,
         *     type: string,
         *     total_tracks: int,
         *     released_at: ?int,
         *     barcode_id: string,
         *     artists: string,
         *     is_deleted: int,
         *     merged_tracks: int,
         *     all_tracks_mapped: int,
         *     is_loaded: int,
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

            $appleType = 'ALBUM';

            if ($row['apple_is_compilation'] === 1) {
                $appleType = 'COMPILATION';
            } elseif ($row['apple_is_single'] === 1) {
                $appleType = 'SINGLE';
            }

            $result[] = [
                'id'                    => $row['id'],
                't_id'                  => $row['t_id'],
                'a_id'                  => $row['a_id'],
                'is_approved'           => $row['is_approved'],
                'is_reissued'           => $row['is_reissued'],
                'spotify_id'            => $row['spotify_id'],
                'spotify_name'          => $row['spotify_name'],
                'spotify_type'          => strtoupper($row['spotify_type']),
                'spotify_released_at'   => $row['spotify_released_at'],
                'spotify_total_tracks'  => $row['spotify_total_tracks'],
                'spotify_upc'           => $row['spotify_upc'],
                'spotify_artists'       => implode(', ', $spotifyArtists),
                'spotify_is_deleted'    => (bool)$row['spotify_is_deleted'],
                'apple_id'              => $row['apple_id'],
                'apple_name'            => $row['apple_name'],
                'apple_type'            => $appleType,
                'apple_released_at'     => $row['apple_released_at'],
                'apple_total_tracks'    => $row['apple_total_tracks'],
                'apple_upc'             => $row['apple_upc'],
                'apple_artists'         => $row['apple_artists'],
                'apple_is_deleted'      => (bool)$row['apple_is_deleted'],
                'tidal_id'              => $row['tidal_id'],
                'tidal_name'            => $row['name'],
                'tidal_type'            => strtoupper($row['type']),
                'tidal_released_at'     => $row['released_at'],
                'tidal_total_tracks'    => $row['total_tracks'],
                'tidal_upc'             => $row['barcode_id'],
                'tidal_artists'         => implode(', ', $tidalArtists),
                'tidal_is_deleted'      => (bool)$row['is_deleted'],
                'merged_tracks'         => $row['merged_tracks'],
                'all_tracks_mapped'     => $row['all_tracks_mapped'],
                'is_loaded'             => $row['is_loaded'],
            ];
        }

        return $result;
    }
}
