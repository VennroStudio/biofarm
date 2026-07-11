<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Media;

use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Components\Storage\StorageInterface;
use App\Modules\Media\Entity\MediaAsset\MediaAssetRepository;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteMediaAction implements RequestHandlerInterface
{
    public function __construct(
        private MediaAssetRepository $repository,
        private StorageInterface $storage,
        private FlusherInterface $flusher,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $asset = $this->repository->getById(Route::getArgumentToInt($request, 'id'));

        $this->storage->delete($asset->path);
        $this->repository->remove($asset);
        $this->flusher->flush();

        return new JsonDataSuccessResponse(1, 200);
    }
}
