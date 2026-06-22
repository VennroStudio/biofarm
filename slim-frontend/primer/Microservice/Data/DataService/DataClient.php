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

/**
 * Contract for data operations used by external modules.
 *
 * Current implementation: InProcess (DataFacadeService in Modules/Data).
 * After full migration to microservice: HttpDataClient in this package.
 *
 * External modules MUST depend on this interface, not on the concrete implementation.
 */
interface DataClient
{
    // ── Cities ──────────────────────────────────────────────────────────

    /**
     * @throws DomainExceptionModule
     */
    public function getCityById(int $id): City;

    /**
     * @param int[] $ids
     * @return City[]
     */
    public function getCitiesByIds(array $ids): array;

    /**
     * @param int[] $ids
     * @return list<array<array-key, mixed>>
     */
    public function getSerializedCitiesByIds(array $ids): array;

    public function findCityById(int $id): ?City;

    public function findRandomCity(int $countryId): ?City;

    // ── Countries ───────────────────────────────────────────────────────

    /**
     * @throws DomainExceptionModule
     */
    public function getCountryById(int $id): Country;

    /**
     * @param int[] $ids
     * @return Country[]
     */
    public function getCountriesByIds(array $ids): array;

    /**
     * @param int[] $ids
     * @return list<array<array-key, mixed>>
     */
    public function getSerializedCountriesByIds(array $ids): array;

    public function findCountryById(int $id): ?Country;

    public function findCountryByName(string $name): ?Country;

    public function findRandomCountry(): ?Country;

    // ── Banks ───────────────────────────────────────────────────────────

    /**
     * @param int[] $ids
     * @return Bank[]
     */
    public function getBanksByIds(array $ids): array;

    // ── Payment Systems ──────────────────────────────────────────────────

    /**
     * @param int[] $ids
     * @return PaymentSystem[]
     */
    public function getPaymentSystemsByIds(array $ids): array;

    // ── Bins ────────────────────────────────────────────────────────────

    public function findBin(string $bin): ?Bin;

    // ── Spaces ──────────────────────────────────────────────────────────

    /**
     * @throws DomainExceptionModule
     */
    public function getSpaceById(int $id): Space;

    public function findSpaceByCityId(int $cityId): ?Space;

    /**
     * @throws DomainExceptionModule
     */
    public function getSpaceGroupById(int $id): SpaceGroup;

    // ── Currencies ───────────────────────────────────────────────────────

    /**
     * @param int[] $ids
     * @return Currency[]
     */
    public function getCurrenciesByIds(array $ids): array;

    /**
     * @throws DomainExceptionModule
     */
    public function getCurrencyById(int $id): Currency;

    public function findCurrencyById(int $id): ?Currency;

    /**
     * @param array{refreshedAt: int, items: list<array{alphabeticCode: string, currentRateSale: float|null, currentRatePurchase: float|null, hash: string, openingRateSale?: float|null, openingRatePurchase?: float|null}>} $data
     * @return list<array{id: int, rate: float|null, diffValue: float, diffPercent: float}>
     */
    public function updateCurrencyRates(array $data): array;

    public function findCurrencyByAlphabeticCode(string $code): ?Currency;

    public function findCurrencyRateByHash(string $hash, string $alphabeticCode): ?CurrencyRate;

    public function findCurrencyIdByCountryId(int $countryId): ?int;

    /**
     * @return array{coefficient: float, percentSafety: float, refreshedAt: int}
     */
    public function getCurrencySettings(): array;
}
