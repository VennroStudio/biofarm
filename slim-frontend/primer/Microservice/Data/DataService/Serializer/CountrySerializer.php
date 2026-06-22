<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Serializer;

use App\Components\Microservice\Data\DataService\Responses\Country;
use App\Components\Microservice\Data\DataService\Responses\CountryIcon;
use App\Components\Microservice\Data\DataService\Responses\CountryPhone;

class CountrySerializer
{
    public function serialize(array|Country|null $country): ?array
    {
        if ($country === null) {
            return null;
        }

        if (\is_array($country)) {
            return $country;
        }

        return [
            'id'        => $country->id,
            'name'      => $country->name,
            'fullName'  => $country->fullName,
            'nameOwn'   => $country->nameOwn,
            'isActual'  => $country->isActual,
            'code'      => $country->code,
            'icons'     => $this->serializeIcon($country->icons),
            'phones'    => $this->serializePhones($country->phones),
        ];
    }

    /**
     * @param array<array|Country|null> $items
     * @return array[]
     */
    public function serializeItems(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $serialized = $this->serialize($item);
            if ($serialized !== null) {
                $result[] = $serialized;
            }
        }

        return $result;
    }

    /**
     * @return array{circle: string|null}|null
     */
    private function serializeIcon(?CountryIcon $icon): ?array
    {
        if ($icon === null) {
            return null;
        }

        return [
            'circle' => $icon->circle,
        ];
    }

    /**
     * @param CountryPhone[]|null $phones
     * @return array<int, array{code: string, length: int, mask: string, isMain: bool}>
     */
    private function serializePhones(?array $phones): array
    {
        if ($phones === null) {
            return [];
        }

        $result = [];
        foreach ($phones as $phone) {
            $result[] = [
                'code'   => $phone->code,
                'length' => $phone->length,
                'mask'   => $phone->mask,
                'isMain' => $phone->isMain,
            ];
        }
        return $result;
    }
}
