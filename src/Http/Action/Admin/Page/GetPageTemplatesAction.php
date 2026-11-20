<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Page;

use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Page\Service\PageTemplateCatalog;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetPageTemplatesAction implements RequestHandlerInterface
{
    public function __construct(
        private PageTemplateCatalog $templates,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        unset($request);

        return new JsonDataResponse($this->templates->all());
    }
}
