<?php

declare(strict_types=1);

namespace App\Components\SpotifyGrab;

use App\Components\SpotifyGrab\Entities\Album;
use App\Components\SpotifyGrab\Entities\Track;
use Exception;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use SpotifyWebAPI\SpotifyWebAPI;
use Throwable;

class SpotifyGrab
{
    private string $market = 'US';
    private string $locale = 'ru';

    public function __construct(
        private readonly SpotifyWebAPI $spotify
    ) {}

    public function setAccessToken(string $accessToken): void
    {
        $this->spotify->setAccessToken($accessToken);
        $this->spotify->setOptions(['return_assoc' => true]);
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /** @throws Exception */
    public function searchAlbum(string $name, ?string $upc = null): ?Album
    {
        $data = $this->spotify->search(
            $name,
            ['album'],
            [
                'market'         => $this->getMarket(),
                'locale'         => $this->getLocale(),
            ]
        );

        if (!isset($data['albums']['items'])) {
            return null;
        }

        /**
         * @var array{
         *     id: string,
         *     album_type: string,
         *     name: string,
         *     release_date: string,
         *     release_date_precision: string,
         *     total_tracks: int,
         *     available_markets: string[]|null,
         *     artists: array{href: string, id: string, name: string, type: string, uri: string}[],
         *     images: array,
         * }[] $items
         */
        $items = (array)$data['albums']['items'];

        $albums = $this->mapAlbumAttributes($items, []);

        $albums = $this->mapAlbumsExtendedInformation($albums);

        foreach ($albums as $album) {
            // echo PHP_EOL . 'SPOTIFY: ' . $album->name . ' ' . $album->upc;

            if ($album->upc === $upc && null !== $upc) {
                return $album;
            }

            if ($album->name === $name) {
                return $album;
            }
        }

        return null;
    }

    /** @return string[] */
    public function getSimilarArtistIds(string $artistId): array
    {
        return [];
        // $data = $this->spotify->getArtistRelatedArtists($artistId);
        //
        // $ids = [];
        //
        // /** @var array{id: string} $artist */
        // foreach ($data['artists'] ?? [] as $artist) {
        //     $ids[] = $artist['id'];
        // }
        //
        // return $ids;
    }

    /** @return string[] */
    public function getArtistTopTracks(string $artistId): array
    {
        $data = $this->spotify->getArtistTopTracks(
            $artistId,
            [
                'market' => $this->getMarket(),
                'locale' => $this->getLocale(),
            ]
        );

        $ids = [];

        /** @var array{id: string} $track */
        foreach ($data['tracks'] ?? [] as $track) {
            $ids[] = $track['id'];
        }

        return $ids;
    }

    /**
     * @param string[] $artistIds
     * @param string[] $trackIds
     * @return string[]
     */
    public function getSimilarAlbumIds(array $artistIds, array $trackIds): array
    {
        return [];
        // $limit = 100;
        // $offset = 0;
        //
        // $trackIds = \array_slice($trackIds, 0, min(4, \count($trackIds)));
        // $countArtists = 5 - \count($trackIds);
        //
        // $data = $this->spotify->getRecommendations([
        //     'seed_artists'   => \array_slice($artistIds, 0, $countArtists),
        //     'seed_tracks'    => $trackIds,
        //     'limit'          => $limit,
        //     'offset'         => $offset,
        //     'market'         => $this->getMarket(),
        //     'locale'         => $this->getLocale(),
        // ]);
        //
        // /** @var string[] $ids */
        // $ids = [];
        //
        // /** @var array{album: array{id: string, name: string}} $track */
        // foreach ($data['tracks'] ?? [] as $track) {
        //     $ids[] = $track['album']['id'];
        // }
        //
        // return $ids;
    }

    public function getAudioFeatures(string $trackId): ?array
    {
        return null;
        // try {
        //     $data = $this->spotify->getAudioFeatures($trackId);
        // } catch (Throwable) {
        //     return null;
        // }
        //
        // if (!isset($data['id'])) {
        //     return null;
        // }
        //
        // return $data;
    }

    public function getAudioAnalysis(string $trackId): ?array
    {
        return null;
        // try {
        //     $data = $this->spotify->getAudioAnalysis($trackId);
        // } catch (Throwable) {
        //     return null;
        // }
        //
        // if (!isset($data['meta'])) {
        //     return null;
        // }
        //
        // return $data;
    }

    /**
     * @return Album[]
     * @throws Exception
     */
    public function getAlbums(string $artistId, string $type, ?int $maxCount = null): array
    {
        $limit = (null !== $maxCount) ? $maxCount : 50;
        $offset = 0;

        $albums = [];

        while (true) {
            $data = $this->spotify->getArtistAlbums(
                $artistId,
                [
                    'limit'          => $limit,
                    'offset'         => $offset,
                    'include_groups' => $type,
                    'market'         => $this->getMarket(),
                    'locale'         => $this->getLocale(),
                ]
            );

            if (!isset($data['items'])) {
                throw new RateLimitException();
            }

            /**
             * @var array{
             *     id: string,
             *     album_type: string,
             *     name: string,
             *     release_date: string,
             *     release_date_precision: string,
             *     total_tracks: int,
             *     available_markets: string[]|null,
             *     artists: array{href: string, id: string, name: string, type: string, uri: string}[],
             *     images: array,
             * }[] $items
             */
            $items = (array)$data['items'];
            $total = (int)($data['total'] ?? 0);

            $albums = $this->mapAlbumAttributes($items, $albums);

            $offset += $limit;

            if ($offset >= $total || (null !== $maxCount && $offset >= $maxCount)) {
                break;
            }
        }

        return $this->mapAlbumsExtendedInformation($albums);
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
            $data = $this->spotify->getAlbums(
                $chunk,
                [
                    'market' => $this->getMarket(),
                    'locale' => $this->getLocale(),
                ]
            );

            if (!isset($data['albums'])) {
                throw new RateLimitException();
            }

            /**
             * @var array{
             *     id: string,
             *     album_type: string,
             *     name: string,
             *     release_date: string,
             *     release_date_precision: string,
             *     total_tracks: int,
             *     available_markets: string[]|null,
             *     artists: array{href: string, id: string, name: string, type: string, uri: string}[],
             *     images: array,
             * }[] $items
             */
            $items = (array)$data['albums'];

            $albums = $this->mapAlbumAttributes($items, $albums);
        }

        return $this->mapAlbumsExtendedInformation($albums);
    }

    /**
     * @param string[] $ids
     * @return Track[]
     * @throws Exception
     */
    public function getTrackByIds(array $ids): array
    {
        $tracks = [];

        $chunks = array_chunk($ids, 50);

        foreach ($chunks as $chunk) {
            $data = $this->spotify->getTracks(
                $chunk,
                [
                    'market' => $this->getMarket(),
                    'locale' => $this->getLocale(),
                ]
            );

            if (!isset($data['tracks'])) {
                throw new RateLimitException();
            }

            /**
             * @var array{
             *     id: string,
             *     type: string,
             *     disc_number: int,
             *     track_number: int,
             *     name: string,
             *     explicit: bool,
             *     is_local: bool,
             *     duration_ms: int,
             *     available_markets: string[],
             *     artists: array,
             *     external_ids: array{
             *          isrc: null|string,
             *      }|null,
             *     linked_from: array{
             *         id: null|string
             *     }|null
             * }[] $items
             */
            $items = (array)$data['tracks'];

            $tracks = $this->mapTracksAttributes($items, $tracks);
        }

        return $tracks;
    }

    /**
     * @return Track[]
     * @throws Exception
     */
    public function getAlbumTracks(string $albumId): array
    {
        $limit = 50;
        $offset = 0;

        $tracks = [];

        while (true) {
            $data = $this->spotify->getAlbumTracks($albumId, [
                'limit'     => $limit,
                'offset'    => $offset,
                'market'    => $this->getMarket(),
                'locale'    => $this->getLocale(),
            ]);

            if (!isset($data['items'])) {
                throw new RateLimitException();
            }

            /**
             * @var array{
             *     id: string,
             *     type: string,
             *     disc_number: int,
             *     track_number: int,
             *     name: string,
             *     explicit: bool,
             *     is_local: bool,
             *     duration_ms: int,
             *     available_markets: string[],
             *     artists: array,
             *     external_ids: array{
             *          isrc: null|string,
             *      }|null,
             *      linked_from: array{
             *          id: null|string
             *      }|null
             * }[] $items
             */
            $items = (array)$data['items'];
            $total = (int)($data['total'] ?? 0);

            $tracks = $this->mapTracksAttributes($items, $tracks);

            $offset += $limit;

            if ($offset >= $total) {
                break;
            }
        }

        return $this->mapTracksExtendedInformation($tracks);
    }

    /**
     * @param Cookie[] $cookies
     * @throws Exception|TokenException
     */
    public function authWhisperify(array $cookies): string
    {
        $browser = (new BrowserFactory())
            ->createBrowser([
                'headless' => true,
                'args' => [
                    '--no-sandbox',
                ],
            ]);

        $page = $browser->createPage();
        /** @psalm-suppress MixedMethodCall */
        $page->setCookies($cookies)->await();

        sleep(5);

        /** @psalm-suppress InternalMethod */
        $page->navigate('https://whisperify.net/login')->waitForNavigation();

        sleep(5);

        /** @psalm-suppress InternalMethod */
        $sessionStorageData = (string)$page->evaluate('
            var data = {};
            for (var i = 0; i < sessionStorage.length; i++) {
                var key = sessionStorage.key(i);
                data[key] = sessionStorage.getItem(key);
            }
            JSON.stringify(data);
        ')->getReturnValue();

        /** @var array{token: string}|empty $sessionStorage */
        $sessionStorage = json_decode($sessionStorageData, true);

        $browser->close();

        if (!isset($sessionStorage['token'])) {
            throw new TokenException('Session storage not found - ' . $sessionStorageData);
        }

        $token = trim($sessionStorage['token']);

        if ($token === '') {
            throw new TokenException('Token is empty - ' . $sessionStorageData);
        }

        return $token;
    }

    /**
     * @param Album[] $albums
     * @return Album[]
     * @throws RateLimitException
     */
    private function mapAlbumsExtendedInformation(array $albums): array
    {
        $albumIds = [];

        foreach ($albums as $album) {
            $albumIds[] = $album->id;
        }

        $items = $this->getAlbumsExtendedInformation($albumIds);

        foreach ($albums as $k => $album) {
            foreach ($items as $item) {
                if ($album->id !== $item['id']) {
                    continue;
                }

                $albums[$k] = new Album(
                    id: $album->id,
                    type: $album->type,
                    name: $album->name,
                    release: $album->release,
                    releasePrecision: $album->releasePrecision,
                    totalTracks: $album->totalTracks,
                    availableMarkets: $album->availableMarkets,
                    artists: $album->artists,
                    upc: $item['external_ids']['upc'] ?? null,
                    images: $album->images,
                    copyrights: $item['copyrights'] ?? [],
                    externalIds: $item['external_ids'] ?? [],
                    genres: $item['genres'] ?? [],
                    label: $item['label'] ?? null,
                    popularity: $item['popularity'] ?? 0
                );
            }
        }

        return $albums;
    }

    /**
     * @return array{
     *     id: string,
     *     copyrights: array,
     *     external_ids: array{
     *         upc?: string
     *     },
     *     genres: array,
     *     label: null|string,
     *     popularity: int,
     * }[]
     * @throws RateLimitException
     */
    private function getAlbumsExtendedInformation(array $albumIds): array
    {
        $result = [];

        $chunks = array_chunk($albumIds, 20);

        foreach ($chunks as $chunk) {
            $data = $this->spotify->getAlbums(
                albumIds: $chunk,
                options: [
                    'market' => $this->getMarket(),
                    'locale' => $this->getLocale(),
                ]
            );

            if (!isset($data['albums'])) {
                throw new RateLimitException();
            }

            /** @var array{
             *     id: string,
             *     copyrights: array,
             *     external_ids: array{
             *         upc?: string
             *     },
             *     genres: array,
             *     label: null|string,
             *     popularity: int,
             * }[] $albums
             */
            $albums = (array)$data['albums'];

            foreach ($albums as $album) {
                $result[] = [
                    'id'            => $album['id'],
                    'copyrights'    => $album['copyrights'] ?? [],
                    'external_ids'  => $album['external_ids'] ?? [],
                    'genres'        => $album['genres'] ?? [],
                    'label'         => $album['label'] ?? null,
                    'popularity'    => $album['popularity'] ?? 0,
                ];
            }
        }

        return $result;
    }

    /**
     * @param Track[] $tracks
     * @return Track[]
     * @throws RateLimitException
     */
    private function mapTracksExtendedInformation(array $tracks): array
    {
        $trackIds = [];

        foreach ($tracks as $track) {
            $trackIds[] = $track->id;
        }

        $items = $this->getTracksExtendedInformation($trackIds);

        foreach ($tracks as $k => $track) {
            foreach ($items as $item) {
                $linkedId = $item['linkedId'] ?? null;

                $isOK = ($track->id === $item['id'] && null === $linkedId) || $track->id === $linkedId;

                if (!$isOK) {
                    continue;
                }

                $tracks[$k] = new Track(
                    id: $item['id'],
                    linkedId: null !== $linkedId ? $track->id : null,
                    type: $track->type,
                    discNumber: $track->discNumber,
                    trackNumber: $track->trackNumber,
                    name: $track->name,
                    explicit: $track->explicit,
                    duration: $track->duration,
                    isLocal: $track->isLocal,
                    availableMarkets: $track->availableMarkets,
                    artists: $track->artists,
                    isrc: $item['isrc'] ?? null,
                );
            }
        }

        return $tracks;
    }

    /**
     * @return array{
     *     id: string,
     *     linkedId: null|string,
     *     isrc: null|string,
     * }[]
     * @throws RateLimitException
     */
    private function getTracksExtendedInformation(array $trackIds): array
    {
        $result = [];

        $chunks = array_chunk($trackIds, 50);

        foreach ($chunks as $chunk) {
            $data = $this->spotify->getTracks(
                trackIds: $chunk,
                options: [
                    'market' => $this->getMarket(),
                    'locale' => $this->getLocale(),
                ]
            );

            if (!isset($data['tracks'])) {
                throw new RateLimitException();
            }

            /** @var array{
             *     id: string,
             *     external_ids: array{
             *         isrc: null|string,
             *     },
             *     linked_from: array{
             *         id: null|string
             *     }|null
             * }[] $tracks
             */
            $tracks = (array)$data['tracks'];

            foreach ($tracks as $track) {
                $externalIds = $track['external_ids'] ?? [];
                $isrc = $externalIds['isrc'] ?? null;

                if ($isrc !== null) {
                    $isrc = strtoupper(trim($isrc));
                }

                $linkedFrom = $track['linked_from'] ?? [];
                $linkedId = $linkedFrom['id'] ?? null;

                $result[] = [
                    'id'        => $track['id'],
                    'linkedId'  => $linkedId,
                    'isrc'      => $isrc,
                ];
            }
        }

        return $result;
    }

    /**
     * @param array{
     *     id: string,
     *     album_type: string,
     *     name: string,
     *     release_date: string,
     *     release_date_precision: string,
     *     total_tracks: int,
     *     available_markets: string[]|null,
     *     artists: array{href: string, id: string, name: string, type: string, uri: string}[],
     *     images: array,
     * }[] $items
     * @param Album[] $albums
     * @return Album[]
     * @throws Exception
     */
    private function mapAlbumAttributes(array $items, array $albums): array
    {
        foreach ($items as $item) {
            if ($item['release_date_precision'] === 'year') {
                $releaseAt = strtotime($item['release_date'] . '-01-01');
            } elseif ($item['release_date_precision'] === 'month') {
                $releaseAt = strtotime($item['release_date'] . '-01');
            } elseif ($item['release_date_precision'] === 'day') {
                $releaseAt = strtotime($item['release_date']);
            } else {
                throw new Exception('UNKNOWN DATE PRECISION: ' . $item['release_date']);
            }

            $albums[] = new Album(
                id: $item['id'],
                type: $item['album_type'],
                name: $item['name'],
                release: $releaseAt,
                releasePrecision: $item['release_date_precision'],
                totalTracks: $item['total_tracks'],
                availableMarkets: $item['available_markets'] ?? [],
                artists: $item['artists'],
                upc: null,
                images: $item['images'],
                copyrights: [],
                externalIds: [],
                genres: [],
                label: null,
                popularity: 0
            );
        }
        return $albums;
    }

    /**
     * @param array{
     *     id: string,
     *     type: string,
     *     disc_number: int,
     *     track_number: int,
     *     name: string,
     *     explicit: bool,
     *     is_local: bool,
     *     duration_ms: int,
     *     available_markets: string[],
     *     artists: array,
     *     external_ids: array{
     *         isrc: null|string,
     *     }|null,
     *     linked_from: array{
     *         id: null|string
     *     }|null
     * }[] $items
     * @param Track[] $tracks
     * @return Track[]
     * @throws Exception
     */
    private function mapTracksAttributes(array $items, array $tracks): array
    {
        foreach ($items as $item) {
            $externalIds = $item['external_ids'] ?? [];
            $isrc = $externalIds['isrc'] ?? null;

            if ($isrc !== null) {
                $isrc = strtoupper(trim($isrc));
            }

            $linkedFrom = $item['linked_from'] ?? [];
            $linkedId = $linkedFrom['id'] ?? null;

            $tracks[] = new Track(
                id: $item['id'],
                linkedId: $linkedId,
                type: $item['type'],
                discNumber: $item['disc_number'],
                trackNumber: $item['track_number'],
                name: $item['name'],
                explicit: $item['explicit'],
                duration: (int)round($item['duration_ms'] / 1000),
                isLocal: $item['is_local'],
                availableMarkets: $item['available_markets'] ?? [],
                artists: $item['artists'],
                isrc: $isrc,
            );
        }
        return $tracks;
    }
}
