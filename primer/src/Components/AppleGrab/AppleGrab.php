<?php

declare(strict_types=1);

namespace App\Components\AppleGrab;

use App\Components\AppleGrab\Entities\Album;
use App\Components\AppleGrab\Entities\Artist;
use App\Components\AppleGrab\Entities\Track;
use Exception;
use PouleR\AppleMusicAPI\APIClient;
use PouleR\AppleMusicAPI\AppleMusicAPI;
use PouleR\AppleMusicAPI\AppleMusicAPIException;
use PouleR\AppleMusicAPI\AppleMusicAPITokenGenerator;
use Symfony\Component\HttpClient\CurlHttpClient;
use Throwable;

class AppleGrab
{
    private AppleMusicAPI $api;
    private string $countryCode = 'US';

    private int $delay = 0;

    /** @throws Exception */
    public function __construct(
        private readonly string $teamId,
        private readonly string $keyId,
        private readonly string $keyFile,
    ) {
        $tokenGenerator = new AppleMusicAPITokenGenerator();
        $jwtToken = $tokenGenerator->generateDeveloperToken($this->teamId, $this->keyId, $this->keyFile);

        if (null === $jwtToken) {
            throw new Exception('JWT token is null');
        }
        $client = new APIClient(new CurlHttpClient());
        $client->setDeveloperToken($jwtToken);
        $client->setResponseType(APIClient::RETURN_AS_ASSOC);

        $this->api = new AppleMusicAPI($client);
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

    /** @throws AppleMusicAPIException|Exception */
    public function getArtist(string $artistId): Artist
    {
        /**
         * @var array{
         *     id: string,
         *     type: string,
         *     attributes: array{
         *         name: string,
         *         genreNames: string[],
         *         artwork: array{
         *             width: int,
         *             height: int,
         *             url: string,
         *         }
         *     }
         * }|null $result
         */
        $result = $this->api->getCatalogArtist($this->getCountryCode(), $artistId)['data'][0] ?? null;

        if (null === $result) {
            throw new Exception('Artist not found');
        }

        $avatar = null;

        if (isset($result['attributes']['artwork'])) {
            if (!str_contains($result['attributes']['artwork']['url'], 'cover.jpg')) {
                $avatar = $result['attributes']['artwork']['url'];
                $avatar = str_replace('{w}', (string)$result['attributes']['artwork']['width'], $avatar);
                $avatar = str_replace('{h}', (string)$result['attributes']['artwork']['height'], $avatar);
            }
        }

        return new Artist(
            id: $result['id'],
            type: $result['type'],
            name: $result['attributes']['name'],
            avatar: $avatar,
            attributes: $result['attributes']
        );
    }

    /**
     * @return Album[]
     * @throws Exception
     */
    public function getAlbums(string $artistId, ?int $maxCount = null): array
    {
        $albums = [];

        $limit = (null !== $maxCount) ? $maxCount : 100;
        $offset = 0;

        while (true) {
            $requestUrl = 'catalog/' . $this->getCountryCode() . '/artists/' . $artistId . '/albums';
            $requestUrl .= '?limit=' . $limit . '&offset=' . $offset;
            $requestUrl .= '&sort=-releaseDate';
            $requestUrl .= '&include=artists';

            try {
                /** @var array $data */
                $data = $this->api->getAPIClient()->apiRequest('GET', $requestUrl);
            } catch (Throwable) {
                throw new RateLimitException('RATE LIMIT EXCEEDED! (' . __FUNCTION__ . ')');
            }

            $this->checkErrors($data, __FUNCTION__);

            /** @var string|null $next */
            $next = $data['next'] ?? null;

            /**
             * @var array{
             *     id: string,
             *     type: string,
             *     href: string,
             *     attributes: array{
             *         copyright: string,
             *         genreNames: string[],
             *         releaseDate: ?string,
             *         upc: string,
             *         isMasteredForItunes: bool,
             *         artwork: array{
             *             width: int,
             *             height: int,
             *             url: string,
             *         },
             *         recordLabel: string,
             *         isCompilation: bool,
             *         trackCount: int,
             *         isSingle: bool,
             *         name: string,
             *         artistName: string,
             *         isComplete: bool,
             *     },
             *     relationships: array{
             *         artists: array{
             *             data: array{
             *                 id: string,
             *                 type: string,
             *                 name: string,
             *                 avatar: ?string,
             *                 attributes: array{
             *                     name: string,
             *                 },
             *             }[]
             *         }
             *      }
             * }[] $items
             */
            $items = (array)($data['data'] ?? []);

            foreach ($items as $item) {
                $attributes = $item['attributes'];

                $artists = [];

                foreach ($item['relationships']['artists']['data'] ?? [] as $artist) {
                    if (!isset($artist['attributes'])) {
                        continue;
                    }

                    $artists[] = new Artist(
                        id: $artist['id'],
                        type: $artist['type'],
                        name: $artist['attributes']['name'],
                        avatar: null,
                        attributes: $artist['attributes'],
                    );
                }

                $image = null;
                $artwork = $attributes['artwork'] ?? null;

                if (null !== $artwork) {
                    $image = $artwork['url'];
                    $image = str_replace('{w}', (string)$artwork['width'], $image);
                    $image = str_replace('{h}', (string)$artwork['height'], $image);
                }

                $albums[] = new Album(
                    id: $item['id'],
                    upc: $attributes['upc'] ?? null,
                    name: $attributes['name'],
                    isCompilation: $attributes['isCompilation'],
                    isSingle: $attributes['isSingle'],
                    releaseAt: isset($attributes['releaseDate']) ? strtotime($attributes['releaseDate']) : null,
                    totalTracks: $attributes['trackCount'],
                    artistsString: $attributes['artistName'],
                    imageCover: $image,
                    videoCover: null, // todo
                    copyright: $attributes['copyright'] ?? null,
                    label: $attributes['recordLabel'] ?? null,
                    genreNames: $attributes['genreNames'] ?? null,
                    attributes: $attributes,
                    artists: $artists
                );
            }

            $offset += $limit;

            if ($next === null || (null !== $maxCount && $offset >= $maxCount)) {
                break;
            }

            sleep($this->getDelay());
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

        $limit = 50;
        $offset = 0;

        while (true) {
            $requestUrl = 'catalog/' . $this->getCountryCode() . '/albums/' . $albumId . '/tracks?limit=' . $limit . '&offset=' . $offset;

            try {
                /** @var array $data */
                $data = $this->api->getAPIClient()->apiRequest('GET', $requestUrl);
            } catch (Throwable) {
                return []; // todo: не было треков у альбома https://music.apple.com/ru/album/onlyfans-feat-hofmannita-single/1576690961
                // throw new RateLimitException();
            }

            $this->checkErrors($data, __FUNCTION__);

            /** @var string|null $next */
            $next = $data['next'] ?? null;

            /**
             * @var array{
             *     id: string,
             *     type: string,
             *     href: string,
             *     attributes: array{
             *         genreNames: string[],
             *         trackNumber: int,
             *         releaseDate: ?string,
             *         durationInMillis: ?int,
             *         isrc: string,
             *         composerName: ?string,
             *         discNumber: ?int,
             *         isAppleDigitalMaster: bool,
             *         hasLyrics: bool,
             *         name: string,
             *         artistName: string,
             *     }
             * }[] $items
             */
            $items = (array)($data['data'] ?? []);

            foreach ($items as $item) {
                $attributes = $item['attributes'];

                $tracks[] = new Track(
                    id: $item['id'],
                    discNumber: $attributes['discNumber'] ?? 1,
                    trackNumber: $attributes['trackNumber'],
                    isrc: $attributes['isrc'] ?? null,
                    name: $attributes['name'],
                    artistsString: $attributes['artistName'],
                    composers: $attributes['composerName'] ?? null,
                    duration: (int)(($attributes['durationInMillis'] ?? 0) / 1000),
                    genreNames: $attributes['genreNames'],
                    attributes: $attributes,
                    artists: []
                );
            }

            $offset += $limit;

            if ($next === null) {
                break;
            }

            sleep($this->getDelay());
        }

        return $tracks;
    }

    /**
     * @return Track[]
     * @throws Exception
     */
    public function getAlbumPlaylists(string $playlistId): array
    {
        $tracks = [];

        $limit = 50;
        $offset = 0;

        while (true) {
            $requestUrl = 'catalog/' . $this->getCountryCode() . '/playlists/' . $playlistId . '/tracks?limit=' . $limit . '&offset=' . $offset . '&include=artists';

            try {
                /** @var array $data */
                $data = $this->api->getAPIClient()->apiRequest('GET', $requestUrl);
            } catch (Throwable) {
                throw new RateLimitException('RATE LIMIT EXCEEDED! (' . __FUNCTION__ . ')');
            }

            $this->checkErrors($data, __FUNCTION__);

            /** @var string|null $next */
            $next = $data['next'] ?? null;

            /**
             * @var array{
             *     id: string,
             *     type: string,
             *     href: string,
             *     attributes: array{
             *         genreNames: string[],
             *         trackNumber: int,
             *         releaseDate: ?string,
             *         durationInMillis: ?int,
             *         isrc: string,
             *         composerName: ?string,
             *         discNumber: ?int,
             *         isAppleDigitalMaster: bool,
             *         hasLyrics: bool,
             *         name: string,
             *         artistName: string,
             *     },
             *     relationships: array{
             *         artists: array{
             *             data: array{
             *                 id: string,
             *                 type: string,
             *                 name: string,
             *                 avatar: ?string,
             *                 attributes: array{
             *                     name: string,
             *                 },
             *             }[]
             *         }
             *     }
             * }[] $items
             */
            $items = (array)($data['data'] ?? []);

            foreach ($items as $item) {
                $attributes = $item['attributes'];

                $artists = [];

                foreach ($item['relationships']['artists']['data'] ?? [] as $artist) {
                    $artists[] = new Artist(
                        id: $artist['id'],
                        type: $artist['type'],
                        name: $artist['attributes']['name'],
                        avatar: null,
                        attributes: $artist['attributes'],
                    );
                }

                $tracks[] = new Track(
                    id: $item['id'],
                    discNumber: $attributes['discNumber'] ?? 1,
                    trackNumber: $attributes['trackNumber'],
                    isrc: $attributes['isrc'] ?? null,
                    name: $attributes['name'],
                    artistsString: $attributes['artistName'],
                    composers: $attributes['composerName'] ?? null,
                    duration: (int)(($attributes['durationInMillis'] ?? 0) / 1000),
                    genreNames: $attributes['genreNames'],
                    attributes: $attributes,
                    artists: $artists
                );
            }

            $offset += $limit;

            if ($next === null) {
                break;
            }

            sleep($this->getDelay());
        }

        return $tracks;
    }

    /** @throws Exception */
    private function checkErrors(array $data, string $function): void
    {
        if (!isset($data['data'])) {
            throw new RateLimitException('RATE LIMIT EXCEEDED! (' . $function . ')');
        }
    }
}
