<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Playlists;

final readonly class Sql
{
    public function get(Query $query): string
    {
        $sql = '
            SELECT
                p.*,
                (SELECT COUNT(*) FROM playlist_track WHERE playlist_id = p.id) as count_tracks,
                (SELECT COUNT(*) FROM playlist_translate WHERE playlist_id = p.id) as count_translates,
                (SELECT COUNT(*) FROM playlist_translate WHERE playlist_id = p.id AND photo_host IS NOT NULL) as count_photos,
                (SELECT COUNT(*) FROM playlist_translate WHERE playlist_id = p.id AND lang = "ru" AND photo_host IS NOT NULL) as count_translates_without_photo_ru,
                (SELECT COUNT(*) FROM playlist_translate WHERE playlist_id = p.id AND lang = "en" AND photo_host IS NOT NULL) as count_translates_without_photo_en
            FROM
                playlists p
        ';

        $where = [];
        $having = [];

        if ($query->type === 0) {
            $having[] = 'count_translates = 0';
        } elseif ($query->type === 1) {
            $having[] = 'count_translates < 110';
        } elseif ($query->type === 2) {
            $having[] = 'count_translates = 110';
        } elseif ($query->type === 3) {
            $having[] = 'count_photos < 110';
        } elseif ($query->type === 4) {
            $having[] = 'count_photos = 110';
        } elseif ($query->type === 5) {
            $having[] = 'count_translates_without_photo_ru = 0';
        } elseif ($query->type === 6) {
            $having[] = 'count_translates_without_photo_en = 0';
        } elseif ($query->type === 7) {
            $having[] = 'count_translates_without_photo_ru = 0 AND count_translates_without_photo_en = 0';
        }

        if (null !== $query->search) {
            $where[] = 'p.name LIKE "%' . $query->search . '%"';
        }

        if (null !== $query->source) {
            $where[] = 'p.type = ' . $query->source;
        }

        if (null !== $query->priority) {
            $where[] = 'p.priority = ' . $query->priority;
        }

        if (null !== $query->isFollowed) {
            $where[] = 'p.is_followed = ' . $query->isFollowed;
        }

        if (\count($where) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $where) . ')';
        }

        if (\count($having) > 0) {
            $sql .= ' HAVING (' . implode(') AND (', $having) . ')';
        }

        $order = $query->sort === 1 ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY p.id ' . $order;

        return $sql;
    }
}
