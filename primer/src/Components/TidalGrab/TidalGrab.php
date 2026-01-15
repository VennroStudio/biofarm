<?php

declare(strict_types=1);

namespace App\Components\TidalGrab;

use App\Components\TidalGrab\Entities\Album;
use App\Components\TidalGrab\Entities\Token;
use App\Components\TidalGrab\Entities\Track;
use DateInterval;
use DuckBug\Duck;
use Exception;
use Throwable;

class TidalGrab
{
    private const API_URL = 'https://openapi.tidal.com/';
    private const AUTH_URL = 'https://auth.tidal.com/v1/oauth2/';
    private string $countryCode = 'US';
    private ?string $accessToken = null;

    private int $delay = 0;

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /** @return int<0, max> */
    public function getDelay(): int
    {
        /** @var int<0, max> */
        return $this->delay;
    }

    /** @throws Exception */
    public function auth(string $clientId, string $clientSecret): Token
    {
        try {
            $encodedCredentials = base64_encode($clientId . ':' . $clientSecret);

            $ch = curl_init(self::AUTH_URL . 'token?grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $encodedCredentials,
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            if (\is_string($response)) {
                /**
                 * @var array{
                 *     access_token: string,
                 *     token_type: string,
                 *     expires_in: int,
                 * } $result
                 */
                $result = (array)json_decode($response, true);

                return new Token(
                    accessToken: $result['access_token'],
                    tokenType: $result['token_type'],
                    expiresIn: $result['expires_in'],
                );
            }
        } catch (Throwable) {
        }

        throw new Exception('Failed get access token');
    }

    /** @throws Exception */
    public function searchArtistId(string $name): ?string
    {
        $data = $this->methodGetV2(
            endpoint: 'v2/searchresults/' . urlencode($name) . '/relationships/artists',
            data: [
                'countryCode' => $this->getCountryCode(),
                'include'     => 'artists',
            ]
        );

        $artistIds = [];

        /**
         * @var array{
         *     id: string
         * }[] $items
         */
        $items = (array)($data['data'] ?? []);

        foreach ($items as $item) {
            $artistIds[] = $item['id'];
        }

        $data = $this->methodGetV2(
            endpoint: 'v2/artists',
            data: [
                'countryCode' => $this->getCountryCode(),
                'include'     => 'albums',
                'filter[id]'  => implode(',', $artistIds),
            ]
        );

        /**
         * @var array{
         *     attributes: array{
         *         name: string,
         *         externalLinks: array{
         *             href: string|null
         *         }[]|null
         *     }
         * }[] $items
         */
        $items = (array)($data['data'] ?? []);

        foreach ($items as $item) {
            if ($item['attributes']['name'] === $name) {
                $url = $item['attributes']['externalLinks'][0]['href'] ?? null;

                if (null !== $url) {
                    $arr = explode('/', $url);
                    return end($arr);
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getSimilarArtistIds(string $artistId): array
    {
        $maxCount = 100;
        $ids = [];
        $cursor = null;

        while (true) {
            $data = $this->methodGetV2(
                endpoint: 'v2/artists/' . $artistId . '/relationships/similarArtists',
                data: [
                    'countryCode' => $this->getCountryCode(),
                    'page[cursor]' => $cursor,
                ]
            );

            $items = (array)($data['data'] ?? []);
            $cursor = $this->getCursor($data);

            /** @var array{id: string} $item */
            foreach ($items as $item) {
                $ids[] = $item['id'];
            }

            if (null === $cursor || \count($ids) >= $maxCount) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getArtistTopTracks(string $artistId): array
    {
        $maxCount = 100;
        $ids = [];
        $cursor = null;

        while (true) {
            $data = $this->methodGetV2(
                endpoint: 'v2/artists/' . $artistId . '/relationships/tracks',
                data: [
                    'countryCode' => $this->getCountryCode(),
                    'collapseBy' => 'NONE',
                    'page[cursor]' => $cursor,
                ]
            );

            $items = (array)($data['data'] ?? []);
            $cursor = $this->getCursor($data);

            /** @var array{id: string} $item */
            foreach ($items as $item) {
                $ids[] = $item['id'];
            }

            if (null === $cursor || \count($ids) >= $maxCount) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getSimilarAlbumIds(string $albumId): array
    {
        $maxCount = 100;
        $ids = [];
        $cursor = null;

        while (true) {
            $data = $this->methodGetV2(
                endpoint: 'v2/albums/' . $albumId . '/relationships/similarAlbums',
                data: [
                    'countryCode' => $this->getCountryCode(),
                    'page[cursor]' => $cursor,
                ]
            );

            $items = (array)($data['data'] ?? []);
            $cursor = $this->getCursor($data);

            /** @var array{id: string} $item */
            foreach ($items as $item) {
                $ids[] = $item['id'];
            }

            if (null === $cursor || \count($ids) >= $maxCount) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @return Album[]
     * @throws Exception
     */
    public function getAlbums(string $artistId, ?int $maxCount = null): array
    {
        $albums = [];
        $cursor = null;

        // if ($artistId === '8834994') {
        //    $cursor = '12hRnhd8AC'; // 60
        // }

        while (true) {
            $data = $this->methodGetV2(
                endpoint: 'v2/artists/' . $artistId . '/relationships/albums',
                data: [
                    'countryCode'   => $this->getCountryCode(),
                    'include'       => 'albums',
                    'page[cursor]'  => $cursor,
                ]
            );

            $this->checkErrors($data, __FUNCTION__);

            /**
             * @var array{
             *     id: string,
             *     attributes: array{
             *         title: string,
             *         barcodeId: string,
             *         numberOfVolumes: int,
             *         numberOfItems: int,
             *         duration: string,
             *         releaseDate: ?string,
             *         copyright: string|null|string[],
             *         imageLinks: array{href: string, meta: array{width: int, height: int}}[],
             *         videoLinks: array{href: string, meta: array{width: int, height: int}}[],
             *         type: string,
             *         availability: array|null,
             *         mediaTags: array|null,
             *     }
             * }[] $items
             */
            $items = (array)($data['included'] ?? []);
            $cursor = $this->getCursor($data);

            $albums = $this->mapAlbumAttributes($items, $albums);

            if (null === $cursor || (null !== $maxCount && \count($albums) >= $maxCount)) {
                break;
            }

            sleep($this->getDelay());
        }

        Duck::get()->debug('TidalGrab -> getAlbums', $albums);

        return $albums;
    }

    /**
     * @param string[] $ids
     * @return Album[]
     * @throws Exception
     */
    public function getAlbumByIds(array $ids): array
    {
        $albums = [];

        $chunks = array_chunk($ids, 20);

        foreach ($chunks as $chunk) {
            $data = $this->methodGetV2(
                endpoint: 'v2/albums',
                data: [
                    'countryCode' => $this->getCountryCode(),
                    'filter[id]'  => implode(',', $chunk),
                ]
            );

            $this->checkErrors($data, __FUNCTION__);

            /**
             * @var array{
             *     id: string,
             *     attributes: array{
             *         title: string,
             *         barcodeId: string,
             *         numberOfVolumes: int,
             *         numberOfItems: int,
             *         duration: string,
             *         releaseDate: ?string,
             *         copyright: string|null|string[],
             *         imageLinks: array{href: string, meta: array{width: int, height: int}}[],
             *         videoLinks: array{href: string, meta: array{width: int, height: int}}[],
             *         type: string,
             *         availability: array|null,
             *         mediaTags: array|null,
             *     }
             * }[] $items
             */
            $items = (array)($data['data'] ?? []);

            $albums = $this->mapAlbumAttributes($items, $albums);
        }

        return $albums;
    }

    /**
     * @return Track[]
     * @throws Exception
     */
    public function getAlbumTracks(string $albumId): array
    {
        $tracks = [];
        $cursor = null;

        while (true) {
            $data = $this->methodGetV2(
                endpoint: 'v2/albums/' . $albumId . '/relationships/items',
                data: [
                    'countryCode'   => $this->getCountryCode(),
                    'include'       => 'items',
                    'page[cursor]'  => $cursor,
                ]
            );

            $this->checkErrors($data, __FUNCTION__);

            /**
             * @var array{
             *     id: string|int,
             *     type: string,
             *     attributes: array{
             *         title: string,
             *         isrc: string,
             *         duration: string,
             *         copyright: ?string,
             *         popularity: ?float,
             *         mediaTags: array,
             *         explicit: bool,
             *         version: ?string,
             *     }
             * }[] $items
             */
            $items = (array)($data['included'] ?? []);
            $cursor = $this->getCursor($data);

            $tracks = $this->mapTrackAttributes($items, $tracks);

            if (null === $cursor) {
                break;
            }

            sleep($this->getDelay());
        }

        usort($tracks, static function (Track $a, Track $b) {
            return $a->id <=> $b->id;
        });

        Duck::get()->debug('TidalGrab -> getAlbumTracks (' . $albumId . ')', $tracks);

        return $tracks;
    }

    /**
     * @param string[] $ids
     * @return Track[]
     * @throws Exception
     */
    public function getTrackByIds(array $ids): array
    {
        $tracks = [];

        $chunks = array_chunk($ids, 20);

        foreach ($chunks as $chunk) {
            $data = $this->methodGetV2(
                endpoint: 'v2/tracks',
                data: [
                    'countryCode' => $this->getCountryCode(),
                    'filter[id]'  => implode(',', $chunk),
                ]
            );

            $this->checkErrors($data, __FUNCTION__);

            /**
             * @var array{
             *     id: string|int,
             *     type: string,
             *     attributes: array{
             *         title: string,
             *         isrc: string,
             *         duration: string,
             *         copyright: ?string,
             *         popularity: ?float,
             *         mediaTags: array,
             *         explicit: bool,
             *         version: ?string,
             *     }
             * }[] $items
             */
            $items = (array)($data['data'] ?? []);

            $tracks = $this->mapTrackAttributes($items, $tracks);
        }

        return $tracks;
    }

    private function getCursor(array $data): ?string
    {
        /** @var string|null $nextUrl */
        $nextUrl = $data['links']['next'] ?? null;

        if (null === $nextUrl) {
            return null;
        }

        parse_str($nextUrl, $params);

        /** @var string|null */
        return $params['page']['cursor'] ?? null;
    }

    /** @throws Exception */
    private function checkErrors(array $data, string $function): void
    {
        if (!isset($data['errors']) && !isset($data['data'])) {
            throw new RateLimitException('RATE LIMIT EXCEEDED! (' . $function . ')');
        }

        if (isset($data['errors']) && \is_array($data['errors'])) {
            if (isset($data['errors'][0]['code'], $data['errors'][0]['detail'])) {
                $code = (string)$data['errors'][0]['code'];
                $detail = (string)$data['errors'][0]['detail'];

                throw new Exception($code . ': ' . $detail);
            }

            throw new Exception(json_encode($data['errors']));
        }
    }

    /** @throws Exception */
    private function methodGetV2(string $endpoint, array $data): array
    {
        try {
            $ch = curl_init(self::API_URL . $endpoint . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/vnd.api+json',
                'Accept: application/vnd.api+json',
                'Authorization: Bearer ' . ($this->accessToken ?? ''),
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

    /**
     * @param array{
     *      id: string,
     *      attributes: array{
     *          title: string,
     *          barcodeId: string,
     *          numberOfVolumes: int,
     *          numberOfItems: int,
     *          duration: string,
     *          releaseDate: ?string,
     *          copyright: string|null|string[],
     *          imageLinks: array{href: string, meta: array{width: int, height: int}}[],
     *          videoLinks: array{href: string, meta: array{width: int, height: int}}[],
     *          type: string,
     *          availability: array|null,
     *          mediaTags: array|null,
     *      }
     *  }[] $items
     * @param Album[] $albums
     * @return Album[]
     */
    private function mapAlbumAttributes(array $items, array $albums): array
    {
        foreach ($items as $item) {
            $attributes = $item['attributes'];

            $imageCovers = [];
            $videoCovers = [];

            if (!empty($attributes['imageLinks'])) {
                foreach ($attributes['imageLinks'] as $imageLink) {
                    $imageCovers[] = [
                        'url'       => $imageLink['href'],
                        'width'     => $imageLink['meta']['width'],
                        'height'    => $imageLink['meta']['height'],
                    ];
                }
            }

            if (!empty($attributes['videoLinks'])) {
                foreach ($attributes['videoLinks'] as $videoLink) {
                    $videoCovers[] = [
                        'url'       => $videoLink['href'],
                        'width'     => $videoLink['meta']['width'],
                        'height'    => $videoLink['meta']['height'],
                    ];
                }
            }

            $copyright = null;

            if (isset($attributes['copyright'])) {
                if (\is_string($attributes['copyright'])) {
                    $copyright = $attributes['copyright'];
                } else {
                    $copyright = implode(', ', $attributes['copyright']);
                }
            }

            $albums[] = new Album(
                id: $item['id'],
                barcodeId: $attributes['barcodeId'] ?? '',
                title: $attributes['title'],
                type: $attributes['type'],
                release: isset($attributes['releaseDate']) ? strtotime($attributes['releaseDate']) : null,
                totalTracks: $attributes['numberOfItems'],
                artists: [],
                imageCovers: \count($imageCovers) > 0 ? $imageCovers : null,
                videoCovers: \count($videoCovers) > 0 ? $videoCovers : null,
                copyright: $copyright,
                mediaMetadata: [
                    'tags' => $attributes['mediaTags'] ?? null,
                    'availability' => $attributes['availability'] ?? null,
                ],
                properties: null,
            );
        }
        return $albums;
    }

    /**
     * @param array{
     *     id: string|int,
     *     type: string,
     *     attributes: array{
     *         title: string,
     *         isrc: string,
     *         duration: string,
     *         copyright: string|null|string[],
     *         popularity: ?float,
     *         mediaTags: array,
     *         explicit: bool,
     *         version: ?string,
     *     }
     * }[] $items
     * @param Track[] $tracks
     * @return Track[]
     * @throws Exception
     */
    private function mapTrackAttributes(array $items, array $tracks): array
    {
        foreach ($items as $item) {
            $attributes = $item['attributes'];

            if ($item['type'] !== 'tracks') {
                continue;
            }

            $duration = new DateInterval($attributes['duration']);
            $seconds = ($duration->h * 3600) + ($duration->i * 60) + $duration->s;

            $copyright = null;

            if (isset($attributes['copyright'])) {
                if (\is_string($attributes['copyright'])) {
                    $copyright = $attributes['copyright'];
                } else {
                    $copyright = implode(', ', $attributes['copyright']);
                }
            }

            $tracks[] = new Track(
                id: (string)$item['id'],
                discNumber: -1,
                trackNumber: \count($tracks) + 1,
                isrc: strtoupper(trim($attributes['isrc'])),
                name: $attributes['title'],
                artists: [],
                explicit: $attributes['explicit'],
                duration: $seconds,
                version: $attributes['version'] ?? null,
                copyright: $copyright,
                mediaMetadata: [
                    'tags' => $attributes['mediaTags'] ?? null,
                ],
                properties: $attributes['explicit'] ? ['content' => ['explicit']] : null,
                popularity: $attributes['popularity'] ?? 0,
            );
        }

        return $tracks;
    }
}
