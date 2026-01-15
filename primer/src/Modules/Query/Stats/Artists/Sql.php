<?php

declare(strict_types=1);

namespace App\Modules\Query\Stats\Artists;

use App\Modules\Constant;

final readonly class Sql
{
    public function get(Query $query): string
    {
        $time = Constant::timeFrom();

        $sql = '
            SELECT
                a.id,
                a.description,
                a.lo_name,
                a.lo_description,
                a.lo_category_id,
                a.priority,
                a.union_id,
                a.spotify_checked_at,
                a.tidal_checked_at,
                a.apple_checked_at,
                a.merged_at,
                a.checked_at,
                a.synchronized_at,
                a.is_automatic,
                s.spotify_count_socials,
                s.tidal_count_socials,
                s.apple_count_socials,
                s.spotify_count_albums,
                s.tidal_count_albums,
                s.apple_count_albums,
                s.count_approved,
                s.count_conflicts,
                s.count_approved_with_tracks,
                s.count_loaded
            FROM
                artists a INNER JOIN artist_stats s ON a.id = s.artist_id
        ';

        $where = [];

        if (null !== $query->search) {
            $where[] = 'a.description LIKE "%' . $query->search . '%"';
        }

        $timeFrom = Constant::timeFrom();

        if ($query->type === 0) {
            $where[] = 'a.merged_at IS NULL';
        } elseif ($query->type === 1) {
            // Обход залитых (все)
            $where[] = 'a.merged_at IS NOT NULL';
        } elseif ($query->type === 2) {
            // Обход залитых (пройденные)
            $where[] = '
                a.spotify_checked_at IS NOT NULL AND
                a.merged_at IS NOT NULL AND
                a.spotify_checked_at >= ' . $timeFrom . ' AND
                a.merged_at >= ' . $timeFrom;
        } elseif ($query->type === 3) {
            // Обход залитых (не пройденные)
            $where[] = '
                a.spotify_checked_at IS NOT NULL AND
                a.merged_at IS NOT NULL AND
                (a.spotify_checked_at < ' . $timeFrom . ' OR a.merged_at < ' . $timeFrom . ')';
        } elseif ($query->type === 4) {
            // Загружаемые
            $where[] = 'a.merged_at IS NOT NULL';
            $where[] = 's.count_loaded < s.count_approved_with_tracks';
        } elseif ($query->type === 99) {
            // Пустые
            $where[] = 's.count_approved = 0';
        } elseif ($query->type === 100) {
            // Без аватарки
            $where[] = 'a.avatar IS NULL';
        } elseif ($query->type === 101) {
            // Без соц сети (Spotify)
            $where[] = 's.spotify_count_socials = 0';
        } elseif ($query->type === 102) {
            // Без соц сети (Tidal)
            $where[] = 's.tidal_count_socials = 0';
        } elseif ($query->type === 103) {
            // Без соц сети (Apple)
            $where[] = 's.apple_count_socials = 0';
        } elseif ($query->type === 201) {
            // Залив новых (Spotify started)
            $where[] = '(a.merged_at IS NULL AND a.spotify_checked_at < ' . $time . ') OR a.spotify_checked_at IS NULL OR a.spotify_checked_at <= 0';
            $where[] = 's.spotify_count_albums > 0';
        } elseif ($query->type === 202) {
            // Залив новых (Tidal started)
            $where[] = 'a.merged_at IS NULL AND (a.spotify_checked_at IS NOT NULL AND a.tidal_checked_at IS NULL)';
            $where[] = 's.tidal_count_albums > 0';
        } elseif ($query->type === 203) {
            // Залив новых (Apple started)
            $where[] = 'a.merged_at IS NULL AND (a.tidal_checked_at IS NOT NULL AND a.apple_checked_at IS NULL)';
            $where[] = 's.apple_count_albums > 0';
        } elseif ($query->type === 301) {
            // Без альбомов (Spotify)
            $where[] = 's.spotify_count_socials > 0 AND s.spotify_count_albums = 0';
        } elseif ($query->type === 302) {
            // Без альбомов (Tidal)
            $where[] = 's.tidal_count_socials > 0 AND s.tidal_count_albums = 0';
        } elseif ($query->type === 303) {
            // Без альбомов (Apple)
            $where[] = 's.apple_count_socials > 0 AND s.apple_count_albums = 0';
        } elseif ($query->type === 801) {
            // Метаданные (пройденные)
            $where[] = 'a.synchronized_at IS NOT NULL';
        } elseif ($query->type === 802) {
            // Метаданные (не пройденные)
            $where[] = 'a.synchronized_at IS NULL';
        } elseif ($query->type === 901) {
            // Созданные автоматически
            $where[] = 'a.is_automatic = 1';
        }

        if (null !== $query->priority) {
            $where[] = 'a.priority = ' . $query->priority;
        }

        if ($query->conflict === 1) {
            $where[] = 's.count_approved - s.count_approved_with_tracks + s.count_conflicts > 0';
        } elseif ($query->conflict === 0) {
            $where[] = 's.count_approved - s.count_approved_with_tracks + s.count_conflicts = 0';
        }

        if (\count($where) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $where) . ')';
        }

        $order = $query->sort === 1 ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY a.priority DESC, a.id ' . $order;

        return $sql;
    }
}
