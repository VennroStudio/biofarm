<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Certificate;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Request\RequestFile;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Storage\DocumentFileValidator;
use App\Components\Storage\FileUploaderService;
use App\Modules\Media\Entity\MediaAsset\MediaAsset;
use App\Modules\Media\Entity\MediaAsset\MediaAssetRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

final readonly class UploadCertificateFileAction implements RequestHandlerInterface
{
    public function __construct(
        private FileUploaderService $uploader,
        private DocumentFileValidator $validator,
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
            throw new DomainExceptionModule(
                module: 'components',
                message: 'error.file_required',
                code: 17,
                status: 422,
            );
        }

        $uploaded = $this->uploader->uploadWithMetadata(
            tmpFilePath: $file->getPath(),
            destinationDir: 'certificates/' . date('Y/m'),
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
}
