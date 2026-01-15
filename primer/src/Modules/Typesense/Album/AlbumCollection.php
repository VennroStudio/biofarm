<?php

declare(strict_types=1);

namespace App\Modules\Typesense\Album;

use Exception;
use Throwable;
use Typesense\Client;

class AlbumCollection
{
    private const COLLECTION_NAME = 'album';
    private int $number = 0;

    public function __construct(
        private readonly Client $client
    ) {}

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getCollectionName(): string
    {
        return self::COLLECTION_NAME . $this->number;
    }

    /** @throws Exception */
    public function createSchema(): void
    {
        try {
            $schema = [
                'name'      => $this->getCollectionName(),
                'fields'    => [
                    [
                        'name'  => 'identifier',
                        'type'  => 'int64',
                    ],
                    [
                        'name'  => 'artist_ids',
                        'type'  => 'int64[]',
                    ],
                    [
                        'name'  => 'name',
                        'type'  => 'string',
                    ],
                    [
                        'name'  => 'name_translit',
                        'type'  => 'string',
                    ],
                    [
                        'name'  => 'is_album',
                        'type'  => 'bool',
                    ],
                    [
                        'name'  => 'total_tracks',
                        'type'  => 'int32',
                    ],
                ],
                'default_sorting_field' => 'identifier',
            ];

            $this->client->collections->create($schema);
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }

    /**
     * @param AlbumDocument[] $documents
     * @throws Exception
     */
    public function upsertDocuments(array $documents): void
    {
        $data = [];

        foreach ($documents as $document) {
            $data[] = [
                'identifier' => $document->id,
                'artist_ids' => $document->artistIds,
                'name' => mb_strtolower(trim($document->name), 'UTF-8'),
                'name_translit' => mb_strtolower(trim($document->nameTranslit), 'UTF-8'),
                'is_album' => $document->isAlbum,
                'total_tracks' => $document->totalTracks,
            ];
        }

        try {
            $this->client->collections[$this->getCollectionName()]->documents->import($data);
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }

    /** @throws Exception */
    public function deleteSchema(): void
    {
        try {
            $this->client->collections[$this->getCollectionName()]->delete();
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }

    /**
     * @return int[]
     * @throws Exception
     */
    public function searchIdentifiers(AlbumQuery $query): array
    {
        $filter = [
            'artist_ids: ' . $query->artistId,
        ];

        if (null !== $query->isAlbum) {
            $filter[] = 'is_album: ' . ($query->isAlbum ? 'true' : 'false');
        }

        try {
            $filterBy = implode(' && ', $filter);

            /** @var array{hits: array{document: array{identifier: int}}[]} $result */
            $result = $this->client->collections[$this->getCollectionName()]->documents->search([
                'query_by'  => 'name,name_translit',
                'q'         => mb_strtolower(trim($query->search), 'UTF-8'),
                'filter_by' => $filterBy,
                'limit'     => $query->limit,
                'sort_by'   => '_text_match:desc',
                'use_cache' => false,
            ]);

            $ids = [];

            foreach ($result['hits'] as $hit) {
                $ids[] = $hit['document']['identifier'];
            }
            return $ids;
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }
}
