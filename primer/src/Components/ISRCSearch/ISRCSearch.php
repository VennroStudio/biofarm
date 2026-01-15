<?php

declare(strict_types=1);

namespace App\Components\ISRCSearch;

use Exception;
use Throwable;

class ISRCSearch
{
    /** @return ISRCInfo[] */
    public function getInfo(string $isrc): array
    {
        $result = [];

        $data = $this->methodPost(
            url: 'https://isrc-api.soundexchange.com/api/ext/recordings',
            data: [
                'searchFields' => [
                    'isrc' => $isrc,
                ],
                'start'         => 0,
                'number'        => 100,
                'showReleases'  => true,
            ]
        );

        /**
         * @var array{
         *     id: string,
         *     duration: string,
         *     recordingVersion: string|null,
         *     recordingType: string,
         *     recordingYear: string,
         *     recordingArtistName: string,
         *     isExplicit: string,
         *     releaseLabel: string,
         *     icpn: string,
         *     releaseDate: string,
         *     genre: string[],
         *     releaseName: string,
         *     releaseArtistName: string,
         *     recordingTitle: string,
         * }[] $items
         */
        $items = $data['recordings'] ?? [];

        foreach ($items as $item) {
            $result[] = new ISRCInfo(
                id: $item['id'],
                duration: $this->durationToSeconds($item['duration']),
                recordingVersion: $item['recordingVersion'] ?? null,
                recordingType: $item['recordingType'] ?? null,
                recordingYear: isset($item['recordingYear']) ? (int)$item['recordingYear'] : null,
                recordingArtistName: $item['recordingArtistName'] ?? null,
                isExplicit: $item['isExplicit'] === 'True',
                releaseLabel: $item['releaseLabel'] ?? null,
                icpn: $item['icpn'] ?? null,
                releaseDate: isset($item['releaseDate']) ? strtotime($item['releaseDate']) : null,
                genre: $item['genre'] ?? [],
                releaseName: $item['releaseName'] ?? null,
                releaseArtistName: $item['releaseArtistName'] ?? null,
                recordingTitle: $item['recordingTitle'] ?? null,
            );
        }

        return $result;
    }

    /** @throws Exception */
    private function getCookie(): string
    {
        try {
            $ch = curl_init('https://isrc-api.soundexchange.com/api/ext/login');

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $response = (string)curl_exec($ch);

            $header_size = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);

            curl_close($ch);

            preg_match('/set-cookie: (.*); expires/', $header, $matchesToken);
            preg_match('/set-cookie: (.*); HttpOnly; Path/', $header, $matchesSession);

            if (isset($matchesToken[1], $matchesSession[1])) {
                return $matchesToken[1] . '; ' . $matchesSession[1];
            }
        } catch (Throwable) {
        }

        throw new Exception('Failed get cookies');
    }

    /** @throws Exception */
    private function methodPost(string $url, array $data): array
    {
        try {
            $data_string = json_encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . \strlen($data_string),
                'Cookie: ' . $this->getCookie(),
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

    private function durationToSeconds(string $duration): int
    {
        $parts = array_reverse(explode(':', $duration));
        $seconds = 0;

        for ($i = 0; $i < \count($parts); ++$i) {
            $seconds += (int)$parts[$i] * 60** $i;
        }

        return (int)$seconds;
    }
}
