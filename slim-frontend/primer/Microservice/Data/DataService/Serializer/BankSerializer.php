<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Serializer;

use App\Components\Microservice\Data\DataService\Responses\Bank;

class BankSerializer
{
    public function serialize(?Bank $bank): ?array
    {
        if ($bank === null) {
            return null;
        }

        return [
            'id'         => $bank->id,
            'name'       => $bank->name,
            'color'      => $bank->color,
            'background' => $bank->background,
            'logo'       => $bank->logo ? [
                'original' => $bank->logo->original,
                'card'     => $bank->logo->card,
            ] : null,
            'icon'       => $bank->icon ? [
                'circle'   => $bank->icon->circle,
                'original' => $bank->icon->original,
                'card'     => $bank->icon->card,
            ] : null,
            'site'       => $bank->site,
            'phone'      => $bank->phone,
            'wiki'       => $bank->wiki,
        ];
    }

    /**
     * @param Bank[] $items
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
