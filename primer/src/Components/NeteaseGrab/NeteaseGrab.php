<?php

declare(strict_types=1);

namespace App\Components\NeteaseGrab;

use App\Components\NeteaseGrab\Entities\Album;
use App\Components\NeteaseGrab\Entities\Artist;
use App\Components\NeteaseGrab\Entities\Track;
use Exception;
use Throwable;

class NeteaseGrab
{
    private const API_URL = 'https://music.xianqiao.wang/neteaseapiv2/';

    /** @throws Exception */
    public function searchLyric(string $trackName, string $artistName, string $albumName): ?string
    {
        $trackId = null;

        foreach ($this->searchTracks($trackName) as $track) {
            $isArtistFound = false;

            foreach ($track->artists as $artist) {
                if ($this->low($artist->name) === $this->low($artistName)) {
                    $isArtistFound = true;
                    break;
                }
            }

            if (
                $isArtistFound &&
                $this->low($track->name) === $this->low($trackName) &&
                $this->low($track->album->name) === $this->low($albumName)
            ) {
                $trackId = $track->id;
                break;
            }
        }

        return (null !== $trackId) ? $this->findLyric($trackId) : null;
    }

    /**
     * @return Track[]
     * @throws Exception
     */
    public function searchTracks(string $search): array
    {
        $data = $this->methodGet(
            endpoint: 'search',
            data: [
                'keywords' => $search,
                'type'     => 1,
                'limit'    => 100,
            ]
        );

        if (!isset($data['result']['songs'])) {
            return [];
        }

        $tracks = [];

        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     duration: int,
         *     album: array{
         *         id: int,
         *         name: string,
         *         publishTime: int,
         *         size: int,
         *     },
         *     artists: array{
         *         id: int,
         *         name: string,
         *     }[]
         * } $song
         */
        foreach ($data['result']['songs'] as $song) {
            $artists = [];

            foreach ($song['artists'] as $artist) {
                $artists[] = new Artist(
                    id: $artist['id'],
                    name: $artist['name'],
                );
            }

            $tracks[] = new Track(
                id: $song['id'],
                name: $song['name'],
                duration: $song['duration'],
                album: new Album(
                    id: $song['album']['id'],
                    name: $song['album']['name'],
                    publishTime: $song['album']['publishTime'],
                    size: $song['album']['size'],
                ),
                artists: $artists
            );
        }

        return $tracks;
    }

    /** @throws Exception */
    public function findLyric(int $trackId): ?string
    {
        $data = $this->methodGet(
            endpoint: 'lyric',
            data: [
                'id' => $trackId,
            ]
        );

        if (!isset($data['lrc']['lyric'])) {
            return null;
        }

        if (!\is_string($data['lrc']['lyric'])) {
            return null;
        }

        $lyrics = $data['lrc']['lyric'];

        $isFound = false;
        foreach (['作词', '作曲'] as $word) {
            if (stripos($lyrics, $word) !== false) {
                $isFound = true;
            }
        }

        if ($isFound) {
            $lines = explode(PHP_EOL, $lyrics);
            $data = \array_slice($lines, 2);
            $lyrics = implode(PHP_EOL, $data);
        }

        if (stripos($lyrics, '[00:00:') !== false) {
            $lyrics = str_replace('[00:', '[', $lyrics);
        }

        if (trim($lyrics) === '' || substr_count($lyrics, '[00') < 3) {
            return null;
        }

        return $lyrics;
    }

    private function low(string $string): string
    {
        return mb_strtolower(trim($string), 'UTF-8');
    }

    /** @throws Exception */
    private function methodGet(string $endpoint, array $data): array
    {
        try {
            $ch = curl_init(self::API_URL . $endpoint . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            if (\is_string($response)) {
                return (array)json_decode($response, true);
            }
        } catch (Throwable) {
        }

        throw new Exception('Failed get data');
    }
}
