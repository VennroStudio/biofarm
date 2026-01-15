<?php

declare(strict_types=1);

namespace App\Http\Action\Table;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Http\Response\HtmlResponse;

final class AlbumsNotFoundAction implements RequestHandlerInterface
{
    public function handle(Request $request): Response
    {
        $html = file_get_contents('/app/public/tables/albums-not-found.html');

        return new HtmlResponse($html);
    }
}
