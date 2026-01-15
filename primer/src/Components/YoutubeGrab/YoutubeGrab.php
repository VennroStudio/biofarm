<?php

declare(strict_types=1);

namespace App\Components\YoutubeGrab;

use Error;
use Throwable;
use YouTube\Models\StreamFormat;
use YouTube\Models\YouTubeConfigData;
use YouTube\YouTubeDownloader;

class YoutubeGrab
{
    private YouTubeDownloader $youtube;
    private ?YouTubeConfigData $configData = null;
    private string $configVideoId;

    public function __construct(string $cookies, string $userAgent, string $configVideoId = 'FXrIzLonac4')
    {
        $this->configVideoId = $configVideoId;

        $this->youtube = new YouTubeDownloader();

        $this->youtube->getBrowser()->setCookieFile($cookies);
        $this->youtube->getBrowser()->setUserAgent($userAgent);
        $this->youtube->getBrowser()->consentCookies();

        $this->setConfigData();
    }

    public function musicSearch(string $search): ?MusicResult
    {
        $key = $this->configData->getApiKey();
        $visitorId = $this->configData->getGoogleVisitorId();
        $clientName = $this->configData->getClientName();
        $clientVersion = $this->configData->getClientVersion();

        $response = $this->youtube->getBrowser()
            ->post('https://music.youtube.com/youtubei/v1/search?prettyPrint=false&key=' . $key, json_encode([
                'context' => [
                    'client' => [
                        'hl' => 'en',
                        'gl' => 'FR',
                        'timeZone' => 'UTC',
                        'utcOffsetMinutes' => 0,
                        'clientName' => 'WEB_REMIX',
                        'clientVersion' => '1.20231120.03.00',
                    ],
                    'user' => ['lockedSafetyMode' => false],
                ],
                'query' => $search,
                'params' => 'EgWKAQIIAWoOEAMQBBAJEAoQBRAQEBU%3D',
            ]), [
                'Content-Type' => 'application/json',
                'Authorization' => 'SAPISIDHASH 1701536322_65ec745b8c8c30c8d957590da1ac60698f4a278b',
                'X-Goog-Visitor-Id' => $visitorId,
                'X-Origin' => 'https://music.youtube.com',
                'X-Youtube-Client-Name' => $clientName,
                'X-Youtube-Client-Version' => $clientVersion,
            ]);

        try {
            $json = json_decode($response->body, true);

            $contents = $json['contents']['tabbedSearchResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'];

            if (null === $contents) {
                return null;
            }

            $contents = end($contents)['musicShelfRenderer']['contents'] ?? null;

            if (null === $contents) {
                return null;
            }

            $flexColumns = $contents[0]['musicResponsiveListItemRenderer']['flexColumns'] ?? null;

            if (null === $flexColumns) {
                return null;
            }

            $name = $flexColumns[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'][0]['text'];

            if (null === $name) {
                return null;
            }

            $videoId = $flexColumns[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'][0]['navigationEndpoint']['watchEndpoint']['videoId'] ?? null;

            if (null === $videoId) {
                return null;
            }

            $items = $flexColumns[1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? [];

            $artists = [];

            foreach ($items as $k => $item) {
                $pageType = $item['navigationEndpoint']['browseEndpoint']['browseEndpointContextSupportedConfigs']['browseEndpointContextMusicConfig']['pageType'] ?? null;

                if ($pageType !== 'MUSIC_PAGE_TYPE_ARTIST' && $k > 0) {
                    continue;
                }

                $artists[] = $item['text'];
            }

            $duration = end($items)['text'];

            return new MusicResult(
                id: $videoId,
                name: $name,
                artists: $artists,
                duration: $duration,
                durationSeconds: $this->durationToSeconds($duration),
                stream: $this->links($videoId)
            );
        } catch (Error|Throwable) {
            return null;
        }
    }

    private function setConfigData(): void
    {
        $page = $this->youtube->getPage('https://music.youtube.com/watch?v=' . $this->configVideoId);
        $this->configData = $page->getYouTubeConfigData();
    }

    private function links(string $videoId): ?StreamFormat
    {
        $qualities = [
            'AUDIO_QUALITY_ULTRAHIGH', 'AUDIO_QUALITY_HIGH',
            'AUDIO_QUALITY_MEDIUM', 'AUDIO_QUALITY_ULTRAMEDIUM',
            'AUDIO_QUALITY_LOW', 'AUDIO_QUALITY_ULTRALOW',
        ];

        try {
            $result = $this->youtube->getDownloadLinks($videoId);

            foreach ($qualities as $quality) {
                foreach ($result->getAllFormats() as $streamFormat) {
                    $isAudio = str_starts_with($streamFormat->mimeType, 'audio');

                    if ($isAudio && $streamFormat->audioQuality === $quality) {
                        return $streamFormat;
                    }
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function durationToSeconds(string $duration): int
    {
        $parts = array_reverse(explode(':', $duration));
        $seconds = 0;

        for ($i = 0; $i < \count($parts); ++$i) {
            $seconds += $parts[$i] * 60** $i;
        }

        return (int)$seconds;
    }
}
