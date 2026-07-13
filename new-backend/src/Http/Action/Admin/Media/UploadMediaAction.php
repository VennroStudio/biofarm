<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Media;

use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Request\RequestFile;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonErrorResponse;
use App\Components\Storage\FileUploaderService;
use App\Components\Storage\ImageFileValidator;
use App\Modules\Media\Entity\MediaAsset\MediaAsset;
use App\Modules\Media\Entity\MediaAsset\MediaAssetRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

final readonly class UploadMediaAction implements RequestHandlerInterface
{
    public function __construct(
        private FileUploaderService $uploader,
        private ImageFileValidator $validator,
        private MediaAssetRepository $repository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $file = RequestFile::extract($request, 'file');
        if ($file === null) {
            return new JsonErrorResponse(1, 'file_required', status: 422);
        }

        $body = (array)$request->getParsedBody();
        $scope = $this->scope((string)($body['scope'] ?? 'admin'));

        $uploaded = $this->uploader->uploadWithMetadata(
            tmpFilePath: $file->getPath(),
            destinationDir: $scope . '/' . date('Y/m'),
            validator: $this->validator,
        );

        $asset = MediaAsset::create(
            path: $uploaded->path,
            url: $uploaded->url,
            mimeType: $uploaded->mimeType,
            size: $uploaded->size,
            width: $uploaded->width,
            height: $uploaded->height,
            originalName: $file->getOriginalFile()->getClientFilename(),
        );

        $this->repository->add($asset);
        $this->flusher->flush();

        return new JsonDataResponse([
            'id'            => $asset->id,
            'path'          => $asset->path,
            'url'           => $asset->url,
            'mime_type'     => $asset->mimeType,
            'size'          => $asset->size,
            'width'         => $asset->width,
            'height'        => $asset->height,
            'original_name' => $asset->originalName,
        ], 201);
    }

    private function scope(string $scope): string
    {
        $scope = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($scope)) ?? 'admin';
        $scope = trim($scope, '-');

        return $scope !== '' ? $scope : 'admin';
    }
}
