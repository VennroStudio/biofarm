<?php

declare(strict_types=1);

namespace App\Components\TidalGrab;

class TidalDL
{
    // /root/.config/tidal_dl_ng/settings.json
    // /root/.config/tidal_dl_ng/token.json

    // tidal-dl-ng dl "https://tidal.com/browse/track/9133880"

    public function setAccessToken(string $token, string $filePath = '../home/app/.config/tidal_dl_ng/token.json'): bool
    {
        // /root/.config/tidal_dl_ng/token.json

        if (is_file($filePath) && filesize($filePath) > 0) {
            return true;
        }

        if (file_put_contents($filePath, $token) !== false) {
            return true;
        }

        return false;
    }

    public function download(int $trackId): bool
    {
        $command = 'tidal-dl-ng dl "https://tidal.com/browse/track/' . $trackId . '"';

        /** @psalm-suppress ForbiddenCode */
        $result = shell_exec($command);

        if (\is_string($result)) {
            return str_contains($result, 'Downloaded item');
        }

        return false;
    }

    private function login(): void
    {
        // Первичная авторизация из контейнера
        // su -s /bin/sh -c 'tidal-dl' www-data
        // su -s /bin/sh -c 'tidal-dl -l "https://tidal.com/browse/track/70973230"' www-data

        // su -s /bin/sh -c 'tidal-dl -o ../var/tidal' www-data
        // su -s /bin/sh -c 'tidal-dl -q Master' www-data
        // su -s /bin/sh -c 'tidal-dl -r P1080' www-data

        // Вывод в файл
        // $name = 'log_' . time();
        // return 'tidal-dl > var/log/' . $name . '.txt';
    }
}
