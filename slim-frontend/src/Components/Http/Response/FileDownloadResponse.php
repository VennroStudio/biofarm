<?php

declare(strict_types=1);

namespace App\Components\Http\Response;

use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Response;

final class FileDownloadResponse extends Response
{
    public function __construct(
        string $fileName,
        string $contentType,
        string $content,
        int $status = 200,
    ) {
        $fileName = $this->escapeFileName($fileName);

        parent::__construct(
            $status,
            new Headers([
                'Content-Type' => $contentType,
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                    $fileName,
                    rawurlencode($fileName),
                ),
            ]),
            new StreamFactory()->createStream($content),
        );
    }

    private function escapeFileName(string $fileName): string
    {
        return str_replace(['\\', '"'], '', $fileName);
    }
}
