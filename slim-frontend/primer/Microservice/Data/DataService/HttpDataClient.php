<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService;

use App\Components\Http\Exception\DomainExceptionModule;
use App\Components\Microservice\Data\DataService\Responses\Bank;
use App\Components\Microservice\Data\DataService\Responses\Bin;
use App\Components\Microservice\Data\DataService\Responses\City;
use App\Components\Microservice\Data\DataService\Responses\Country;
use App\Components\Microservice\Data\DataService\Responses\Currency;
use App\Components\Microservice\Data\DataService\Responses\CurrencyRate;
use App\Components\Microservice\Data\DataService\Responses\PaymentSystem;
use App\Components\Microservice\Data\DataService\Responses\Space;
use App\Components\Microservice\Data\DataService\Responses\SpaceGroup;
use DuckBug\Duck;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Translation\Translator;
use Throwable;

/**
 * HTTP implementation of DataClient.
 *
 * Talks to data-service internal API.
 * Used when Data module runs as a separate microservice.
 *
 * Contracts:
 * - GET /api/v1/cities/{id}                   -> { "data": {...} }
 * - GET /api/v1/cities/by-ids?ids=1,2,3       -> { "data": [...] }
 * - GET /api/v1/cities?countryId=5             -> { "data": { "items": [...] } }
 * - GET /api/v1/countries/{id}                 -> { "data": {...} }
 * - GET /api/v1/countries/by-ids?ids=1,2,3     -> { "data": [...] }
 * - GET /api/v1/countries?search=name          -> { "data": { "items": [...] } }
 * - GET /api/v1/countries                      -> { "data": { "items": [...] } }
 * - GET /api/v1/bins/{bin}                     -> { "data": {...} }
 * - GET /api/v1/spaces/{id}                    -> { "data": { "space": {id, name, group:int, main:{id,name}, cities:[...]} } }
 * - GET /api/v1/spaces/by-city-id/{cityId}     -> { "data": { "space": {id, name, group:int, main:{id,name}, cities:[...]} | null } }
 * - GET /api/v1/space-groups/{id}             -> { "data": {...} }
 */
final readonly class HttpDataClient implements DataClient
{
    public function __construct(
        private Client $client,
        private string $host,
        private string $token,
        private Duck $duck,
        private Translator $translator,
    ) {}

    // ── Cities ──────────────────────────────────────────────────────────

    #[Override]
    public function getCityById(int $id): City
    {
        $result = $this->httpGet('/api/v1/cities/' . $id);
        $item = $this->extractData($result);

        if ($item === null) {
            throw new DomainExceptionModule(
                module: 'data',
                message: 'error.data.city_not_found',
                code: 1
            );
        }

        /** @var array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null} $item */
        return City::fromArray($item);
    }

    #[Override]
    public function getCitiesByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/cities/by-ids', ['ids' => implode(',', $ids)]);

            /** @var array<int, array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null}> $items */
            $items = $this->extractDataList($result);

            return City::fromArrayList($items);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function getSerializedCitiesByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/cities/by-ids', ['ids' => implode(',', $ids)]);

            /** @var list<array<array-key, mixed>> */
            return $this->extractDataList($result);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function findCityById(int $id): ?City
    {
        try {
            $result = $this->httpGet('/api/v1/cities/' . $id);
            $item = $this->extractData($result);

            if ($item === null) {
                return null;
            }

            /** @var array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null} $item */
            return City::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findRandomCity(int $countryId): ?City
    {
        try {
            $result = $this->httpGet('/api/v1/cities', ['countryId' => $countryId]);

            /** @var array<int, array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null}> $items */
            $items = $this->extractItems($result);

            if ($items === []) {
                return null;
            }

            $randomKey = array_rand($items);
            return City::fromArray($items[$randomKey]);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    // ── Countries ───────────────────────────────────────────────────────

    #[Override]
    public function getCountryById(int $id): Country
    {
        $result = $this->httpGet('/api/v1/countries/' . $id);
        $item = $this->extractData($result);

        if ($item === null) {
            throw new DomainExceptionModule(
                module: 'data',
                message: 'error.data.country_not_found',
                code: 1
            );
        }

        /** @var array{id: int, name: string, fullName?: string|null, nameOwn?: string|null, isActual: bool, code?: string|null, icons?: array{circle?: string|null}|null, phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null} $item */
        return Country::fromArray($item);
    }

    #[Override]
    public function getCountriesByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/countries/by-ids', ['ids' => implode(',', $ids)]);

            /** @var array<int, array{id: int, name: string, fullName?: string|null, nameOwn?: string|null, isActual: bool, code?: string|null, icons?: array{circle?: string|null}|null, phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null}> $items */
            $items = $this->extractDataList($result);

            return Country::fromArrayList($items);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function getSerializedCountriesByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/countries/by-ids', ['ids' => implode(',', $ids)]);

            /** @var list<array<array-key, mixed>> */
            return $this->extractDataList($result);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function findCountryById(int $id): ?Country
    {
        try {
            $result = $this->httpGet('/api/v1/countries/' . $id);
            $item = $this->extractData($result);

            if ($item === null) {
                return null;
            }

            /** @var array{id: int, name: string, fullName?: string|null, nameOwn?: string|null, isActual: bool, code?: string|null, icons?: array{circle?: string|null}|null, phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null} $item */
            return Country::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findCountryByName(string $name): ?Country
    {
        try {
            $result = $this->httpGet('/api/v1/countries', ['search' => $name]);

            /** @var array<int, array{id: int, name: string, fullName?: string|null, nameOwn?: string|null, isActual: bool, code?: string|null, icons?: array{circle?: string|null}|null, phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null}> $items */
            $items = $this->extractItems($result);

            if ($items === []) {
                return null;
            }

            foreach ($items as $item) {
                $country = Country::fromArray($item);
                if (mb_strtolower($country->name, 'UTF-8') === mb_strtolower($name, 'UTF-8')) {
                    return $country;
                }
            }

            return null;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findRandomCountry(): ?Country
    {
        try {
            $result = $this->httpGet('/api/v1/countries');

            /** @var array<int, array{id: int, name: string, fullName?: string|null, nameOwn?: string|null, isActual: bool, code?: string|null, icons?: array{circle?: string|null}|null, phones?: array<int, array{code: string, length: int, mask: string, isMain: bool}>|null}> $items */
            $items = $this->extractItems($result);

            if ($items === []) {
                return null;
            }

            $randomKey = array_rand($items);
            return Country::fromArray($items[$randomKey]);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    // ── Banks ───────────────────────────────────────────────────────────

    #[Override]
    public function getBanksByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/banks/by-ids', ['ids' => implode(',', $ids)]);

            /** @var array<int, array{id: int, name: string, background?: string[]|null, color?: string|null, icon?: array{card?: string|null, circle?: string|null, original?: string|null}|null, logo?: array{card?: string|null, original?: string|null}|null, phone?: string|null, site?: string|null, wiki?: string|null}> $items */
            $items = $this->extractDataList($result);

            return Bank::fromArrayList($items);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    // ── Payment Systems ──────────────────────────────────────────────────

    #[Override]
    public function getPaymentSystemsByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/payment-systems/by-ids', ['ids' => implode(',', $ids)]);

            /** @var array<int, array{id: int, name: string, logo?: string|null, site?: string|null, wiki?: string|null}> $items */
            $items = $this->extractDataList($result);

            return PaymentSystem::fromArrayList($items);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    // ── Bins ────────────────────────────────────────────────────────────

    #[Override]
    public function findBin(string $bin): ?Bin
    {
        try {
            $result = $this->httpGet('/api/v1/bins/' . $bin);
            $item = $this->extractData($result);

            if ($item === null) {
                return null;
            }

            /** @var array{bin: string, category?: string|null, type?: string|null, bank?: array{id: int, name: string, color?: string|null, background?: string[]|null, logo?: array{card?: string|null, original?: string|null}|null, icon?: array{card?: string|null, circle?: string|null, original?: string|null}|null, phone?: string|null, site?: string|null, wiki?: string|null}|null, paymentSystem?: array{id: int, name: string, logo?: string|null, site?: string|null, wiki?: string|null}|null} $item */
            return Bin::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    // ── Spaces ──────────────────────────────────────────────────────────

    #[Override]
    public function getSpaceById(int $id): Space
    {
        $result = $this->httpGet('/api/v1/spaces/' . $id);
        $data = $this->extractData($result);
        $item = $data['space'] ?? null;

        if (!\is_array($item)) {
            throw new DomainExceptionModule(
                module: 'data',
                message: 'error.data.space_not_found',
                code: 1
            );
        }

        /** @var array{id: int, name: string, group: int, main?: array{id: int, name: string}|null, cities?: list<array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null}>|null} $item */
        return Space::fromArray($item);
    }

    #[Override]
    public function findSpaceByCityId(int $cityId): ?Space
    {
        try {
            $result = $this->httpGet('/api/v1/spaces/by-city-id/' . $cityId);
            $data = $this->extractData($result);
            $item = $data['space'] ?? null;

            if (!\is_array($item)) {
                return null;
            }

            /** @var array{id: int, name: string, group: int, main?: array{id: int, name: string}|null, cities?: list<array{id: int, name: string, area?: string|null, region?: string|null, latitude?: float|null, longitude?: float|null, timezone?: int|null, population?: int|null, foundationYear?: int|null}>|null} $item */
            return Space::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function getSpaceGroupById(int $id): SpaceGroup
    {
        $result = $this->httpGet('/api/v1/space-groups/' . $id);
        $item = $this->extractData($result);

        if ($item === null) {
            throw new DomainExceptionModule(
                module: 'data',
                message: 'error.data.space_group_not_found',
                code: 1
            );
        }

        /** @var array{id: int, name: string, status: int} $item */
        return SpaceGroup::fromArray($item);
    }

    // ── Currencies ───────────────────────────────────────────────────────

    #[Override]
    public function getCurrenciesByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, \is_int(...)));
        if ($ids === []) {
            return [];
        }

        try {
            $result = $this->httpGet('/api/v1/currencies/by-ids', ['ids' => implode(',', $ids)]);

            /** @var list<array{id: int, icon?: string|null, alphabeticCode?: string|null, numericCode?: string|null, symbol?: string|null, name?: string|null, rate?: float|null, diffValue?: float|int|null, diffPercent?: float|int|null}> $items */
            $items = $this->extractDataList($result);

            return Currency::fromArrayList($items);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function getCurrencyById(int $id): Currency
    {
        $result = $this->httpGet('/api/v1/currencies/' . $id);
        $item = $this->extractData($result);

        if ($item === null) {
            throw new DomainExceptionModule(
                module: 'data',
                message: 'error.data.currency_not_found',
                code: 1
            );
        }

        /** @var array{id: int, icon?: string|null, alphabeticCode?: string|null, numericCode?: string|null, symbol?: string|null, name?: string|null, rate?: float|null, diffValue?: float|int|null, diffPercent?: float|int|null} $item */
        return Currency::fromArray($item);
    }

    #[Override]
    public function findCurrencyById(int $id): ?Currency
    {
        try {
            $result = $this->httpGet('/api/v1/currencies/' . $id);
            /** @var mixed $item */
            $item = $this->extractData($result);

            if ($item === null || !\is_array($item) || !isset($item['id']) || $item['id'] === null) {
                return null;
            }

            /** @var array{id: int, icon?: string|null, alphabeticCode?: string|null, numericCode?: string|null, symbol?: string|null, name?: string|null, rate?: float|null, diffValue?: float|int|null, diffPercent?: float|int|null} $item */
            return Currency::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findCurrencyByAlphabeticCode(string $code): ?Currency
    {
        try {
            $result = $this->httpGet('/api/v1/currencies/by-code', ['code' => $code]);
            /** @var mixed $item */
            $item = $this->extractData($result);

            if ($item === null || !\is_array($item) || !isset($item['id']) || $item['id'] === null) {
                return null;
            }

            /** @var array{id: int, icon?: string|null, alphabeticCode?: string|null, numericCode?: string|null, symbol?: string|null, name?: string|null, rate?: float|null, diffValue?: float|int|null, diffPercent?: float|int|null, hash?: string|null, currentRateSale?: float|null, currentRatePurchase?: float|null} $item */
            return Currency::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findCurrencyRateByHash(string $hash, string $alphabeticCode): ?CurrencyRate
    {
        try {
            $result = $this->httpGet('/api/v1/currencies/rates/by-hash', [
                'hash' => $hash,
                'alphabeticCode' => $alphabeticCode,
            ]);
            $item = $this->extractData($result);

            if ($item === null) {
                return null;
            }

            /** @var array{rateSale?: float|null, ratePurchase?: float|null, time: int} $item */
            return CurrencyRate::fromArray($item);
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function findCurrencyIdByCountryId(int $countryId): ?int
    {
        try {
            $result = $this->httpGet('/api/v1/currencies/by-country', ['countryId' => $countryId]);
            $item = $this->extractData($result);

            if ($item === null || !isset($item['currencyId'])) {
                return null;
            }

            return (int)$item['currencyId'];
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    #[Override]
    public function updateCurrencyRates(array $data): array
    {
        try {
            $result = $this->httpPut('/api/v1/currencies/rates', $data);
            $responseData = $this->extractData($result);

            if ($responseData === null || !isset($responseData['items']) || !\is_array($responseData['items'])) {
                return [];
            }

            /** @var list<array{id: int, rate: float|null, diffValue: float, diffPercent: float}> */
            return $responseData['items'];
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return [];
        }
    }

    #[Override]
    public function getCurrencySettings(): array
    {
        try {
            $result = $this->httpGet('/api/v1/currencies/settings');
            $item = $this->extractData($result);

            if ($item === null) {
                return ['coefficient' => 1.0, 'percentSafety' => 0.0, 'refreshedAt' => 0];
            }

            /** @var array{coefficient: float, percentSafety: float, refreshedAt: int} $item */
            return $item;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return ['coefficient' => 1.0, 'percentSafety' => 0.0, 'refreshedAt' => 0];
        }
    }

    // ── HTTP helpers ────────────────────────────────────────────────────

    private function httpGet(string $uri, array $query = []): ?array
    {
        try {
            $response = $this->client->request('GET', $this->host . $uri, [
                'query' => $query,
                'http_errors' => false,
                'headers' => [
                    'Accept-Language' => $this->translator->getLocale(),
                    'X-SERVICE-TOKEN' => $this->token,
                ],
            ]);

            /** @var mixed $data */
            $data = json_decode((string)$response->getBody(), true);

            return \is_array($data) ? $data : null;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    private function httpPut(string $uri, array $body): ?array
    {
        try {
            $response = $this->client->request('PUT', $this->host . $uri, [
                'json' => $body,
                'http_errors' => false,
                'headers' => [
                    'Accept-Language' => $this->translator->getLocale(),
                    'X-SERVICE-TOKEN' => $this->token,
                    'Content-Type' => 'application/json',
                ],
            ]);

            /** @var mixed $data */
            $data = json_decode((string)$response->getBody(), true);

            return \is_array($data) ? $data : null;
        } catch (Throwable $e) {
            $this->duck->quack($e);
            return null;
        }
    }

    private function extractData(?array $response): ?array
    {
        if (!\is_array($response) || !isset($response['data']) || !\is_array($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    private function extractDataList(?array $response): array
    {
        $data = $this->extractData($response);

        return \is_array($data) ? $data : [];
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private function extractItems(?array $response): array
    {
        $data = $this->extractData($response);

        if (!\is_array($data)) {
            return [];
        }

        $items = $data['items'] ?? null;

        if (!\is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn (mixed $item): bool => \is_array($item)));
    }
}
