<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\PossibleArtists;

use App\Modules\Entity\Playlist\Playlist;

final readonly class Sql
{
    public function get(Query $query): string
    {
        $sql = '
            SELECT
                p.*
            FROM
                possibly_artists p
        ';

        $where = [];
        $where[] = 'p.artist_id IS NULL';

        if (null !== $query->search) {
            $where[] = 'p.name LIKE "%' . $query->search . '%"';
        }

        if ($query->source === Playlist::TYPE_SPOTIFY) {
            $where[] = 'p.spotify_id IS NOT NULL';
        } elseif ($query->source === Playlist::TYPE_APPLE) {
            $where[] = 'p.apple_id IS NOT NULL';
        } elseif ($query->source === Playlist::TYPE_TIDAL) {
            $where[] = 'p.tidal_id IS NOT NULL';
        }

        if (null !== $query->playlist_id) {
            $where[] = 'p.playlist_id = ' . $query->playlist_id;
        }

        if (\count($where) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $where) . ')';
        }

        $order = $query->sort === 1 ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY p.id ' . $order;

        return $sql;
    }
}
