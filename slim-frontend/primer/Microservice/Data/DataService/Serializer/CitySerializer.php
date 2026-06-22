<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Serializer;

use App\Components\Microservice\Data\DataService\Responses\City;

class CitySerializer
{
    public function serialize(array|City|null $city): ?array
    {
        if ($city === null) {
            return null;
        }

        if (\is_array($city)) {
            return $city;
        }

        return [
            'id'             => $city->id,
            'name'           => $city->name,
            'area'           => $city->area,
            'region'         => $city->region,
            'latitude'       => $city->latitude,
            'longitude'      => $city->longitude,
            'timezone'       => $city->timezone,
            'population'     => $city->population,
            'foundationYear' => $city->foundationYear,
        ];
    }

    /**
     * @param array<array-key, array|City|null> $items
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
}
