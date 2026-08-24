<?php

declare(strict_types=1);

namespace App\Components\Integration\Bitrix24;

final readonly class Bitrix24CrmGateway
{
    private const int DEAL_ENTITY_TYPE_ID = 2;
    private const int CONTACT_ENTITY_TYPE_ID = 3;
    private const string DEAL_OWNER_TYPE = 'D';

    public function __construct(
        private Bitrix24Client $client,
    ) {}

    /**
     * @param array<string, mixed> $fields
     */
    public function addDeal(string $webhookUrl, array $fields): ?int
    {
        $response = $this->client->call($webhookUrl, 'crm.item.add', [
            'entityTypeId' => self::DEAL_ENTITY_TYPE_ID,
            'fields'       => $fields,
        ]);

        return $this->extractItemId($response);
    }

    public function addContact(string $webhookUrl, string $name, ?string $phone, ?string $email): ?int
    {
        $fields = [
            'name' => $name !== '' ? $name : 'Клиент сайта',
        ];

        $multifields = [];
        if ($phone !== null && $phone !== '') {
            $multifields[] = [
                'typeId'    => 'PHONE',
                'valueType' => 'WORK',
                'value'     => $phone,
            ];
        }

        if ($email !== null && $email !== '') {
            $multifields[] = [
                'typeId'    => 'EMAIL',
                'valueType' => 'WORK',
                'value'     => $email,
            ];
        }

        if ($multifields !== []) {
            $fields['fm'] = $multifields;
        }

        $response = $this->client->call($webhookUrl, 'crm.item.add', [
            'entityTypeId' => self::CONTACT_ENTITY_TYPE_ID,
            'fields'       => $fields,
        ]);

        return $this->extractItemId($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function dealFields(string $webhookUrl): array
    {
        return $this->client->call($webhookUrl, 'crm.item.fields', [
            'entityTypeId' => self::DEAL_ENTITY_TYPE_ID,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function contactFields(string $webhookUrl): array
    {
        return $this->client->call($webhookUrl, 'crm.item.fields', [
            'entityTypeId' => self::CONTACT_ENTITY_TYPE_ID,
        ]);
    }

    /**
     * @param list<array{productName: string, price: int, quantity: int}> $rows
     */
    public function setDealProductRows(string $webhookUrl, int $dealId, array $rows): void
    {
        $this->client->call($webhookUrl, 'crm.item.productrow.set', [
            'ownerType'   => self::DEAL_OWNER_TYPE,
            'ownerId'     => $dealId,
            'productRows' => $rows,
        ]);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractItemId(array $response): ?int
    {
        $id = $response['result']['item']['id'] ?? null;

        if (!\is_int($id) && !\is_string($id)) {
            return null;
        }

        $itemId = (int)$id;

        return $itemId > 0 ? $itemId : null;
    }
}
