<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Serializer;

use App\Components\Microservice\Data\DataService\Responses\PaymentSystem;

class PaymentSystemSerializer
{
    public function serialize(?PaymentSystem $system): ?array
    {
        if ($system === null) {
            return null;
        }

        return [
            'id'   => $system->id,
            'name' => $system->name,
            'logo' => $system->logo,
            'site' => $system->site,
            'wiki' => $system->wiki,
        ];
    }

    /**
     * @param PaymentSystem[] $items
     */
    public function serializeItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->serialize($item);
        }
        return $result;
    }
}
