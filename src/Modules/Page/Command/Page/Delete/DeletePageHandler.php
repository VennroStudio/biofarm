<?php

declare(strict_types=1);

namespace App\Modules\Page\Command\Page\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Page\Entity\Page\PageRepository;
use DateMalformedStringException;

final readonly class DeletePageHandler
{
    public function __construct(
        private PageRepository $pages,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(DeletePageCommand $command): void
    {
        $page = $this->pages->getById($command->pageId);
        $page->markDeleted();
        $this->cacher->deleteTag('pages');
        $this->cacher->deleteTag('seo');
        $this->flusher->flush();
    }
}
