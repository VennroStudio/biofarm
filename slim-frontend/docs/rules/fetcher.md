# Fetcher / Query

Fetcher читает данные через DBAL `Connection` и возвращает ReadModel.
Repository для чтения не используется.

**Расположение:** `src/Modules/{Module}/Query/{Entity}/{Action}/`
- `{Entity}{Action}Query.php`
- `{Entity}{Action}Fetcher.php`

---

## Get и Find

`Get` — запись обязательна.

- нашел — возвращает ReadModel
- не нашел — бросает `DomainExceptionModule`
- пример: `GetById`

`Find` — отсутствие результата нормально.

- одна запись: ReadModel или `null`
- список: `list<ReadModel>` или `ModelCountItemsResult`
- пустой список — нормальный результат
- пример: `FindByEmail`, `FindAll`, `FindByIds`

`GetBySelect` в проекте используется как короткий список для select. Это список, а не обязательная одиночная запись.

---

## Query

```php
final readonly class {Entity}GetByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
```

Query списка не `readonly`, потому что заполняется денормализацией.

```php
use Symfony\Component\Validator\Constraints as Assert;

final class {Entity}FindAllQuery
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(100)]
    public int $perPage = 20;

    public ?string $search = null;

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
```

---

## Fetcher

Fetcher может принимать ReadModel-класс вторым аргументом, если один query-сценарий нужен в разных формах ответа.

```php
use App\Components\Exception\DomainExceptionModule;
use App\Components\ReadModel\ReadModelFields;
use App\Modules\{Module}\ReadModel\{Entity}\Interface\{Entity}ModelInterface;
use App\Modules\{Module}\ReadModel\{Entity}\{Entity}Details;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class {Entity}GetByIdFetcher
{
    private const string TABLE = '{table_name}';

    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @template T of {Entity}ModelInterface
     * @param class-string<T> $modelClass
     * @return T
     * @throws Exception
     */
    public function fetch({Entity}GetByIdQuery $query, string $modelClass = {Entity}Details::class): {Entity}ModelInterface
    {
        $row = $this->connection->createQueryBuilder()
            ->select(...ReadModelFields::select($modelClass::fields()))
            ->from(self::TABLE)
            ->where('id = :id')
            ->setParameter('id', $query->id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            throw new DomainExceptionModule(
                module: '{module}',
                message: 'error.{entity}_not_found',
                code: 1
            );
        }

        return $modelClass::fromRow($row);
    }
}
```

Fetcher не держит несколько ручных `select()` под разные DTO. Нужные поля описывает ReadModel через `fields()`.

---

## Список с пагинацией

```php
use App\Components\ReadModel\ModelCountItemsResult;
use App\Components\ReadModel\ReadModelFields;

$qb = $this->connection->createQueryBuilder()
    ->from(self::TABLE);

if ($query->search !== null && $query->search !== '') {
    $qb->andWhere('name LIKE :search')
        ->setParameter('search', '%' . $query->search . '%');
}

$countQb = clone $qb;
$total = (int) $countQb->select('COUNT(id)')->executeQuery()->fetchOne();

$rows = $qb->select(...ReadModelFields::select($modelClass::fields()))
    ->orderBy('id', 'DESC')
    ->setFirstResult($query->getOffset())
    ->setMaxResults($query->perPage)
    ->executeQuery()
    ->fetchAllAssociative();

return new ModelCountItemsResult(
    items: $modelClass::fromRows($rows),
    count: $total,
);
```

---

## Select-списки

Для select-списков используется короткая ReadModel, обычно `{Entity}IdName`.

```php
public function fetch({Entity}GetBySelectQuery $query, string $modelClass = {Entity}IdName::class): array
{
    $rows = $this->connection->createQueryBuilder()
        ->select(...ReadModelFields::select($modelClass::fields()))
        ->from(self::TABLE)
        ->orderBy('name', 'ASC')
        ->executeQuery()
        ->fetchAllAssociative();

    return $modelClass::fromRows($rows);
}
```

---

## IN

Для `IN (:ids)` используется `ArrayParameterType::INTEGER`.
Пустой список сразу возвращает `[]`.

```php
use Doctrine\DBAL\ArrayParameterType;

if ($query->ids === []) {
    return [];
}

$rows = $this->connection->createQueryBuilder()
    ->select(...ReadModelFields::select($modelClass::fields()))
    ->from(self::TABLE)
    ->where('parent_id IN (:ids)')
    ->setParameter('ids', $query->ids, ArrayParameterType::INTEGER)
    ->executeQuery()
    ->fetchAllAssociative();
```

---

## JOIN

Если связанный Fetcher нужен для фильтрации основного списка, он может дать `joinForFilter()`.

```php
use Doctrine\DBAL\Query\QueryBuilder;

private const string TABLE = 'order_items';
public const string ALIAS = 'oi';

public function joinForFilter(QueryBuilder $qb, string $alias): void
{
    $qb->leftJoin($alias, self::TABLE, self::ALIAS, self::ALIAS . '.order_id = ' . $alias . '.id');
}
```

При JOIN один-ко-многим:

- `COUNT(DISTINCT {main_alias}.id)`
- `groupBy('{main_alias}.id')`

Если используется `ReadModelFields`, alias основной таблицы передается вторым аргументом.
JOIN-поля в `fields()` указываются с alias.

```php
// ReadModel
public static function fields(): array
{
    return [
        'id' => 'id',
        'processing_id' => 'processing_id',
        'processing_name' => 'p.name',
    ];
}
```

```php
$rows = $this->connection->createQueryBuilder()
    ->select(...ReadModelFields::select($modelClass::fields(), 'mp'))
    ->from(self::TABLE, 'mp')
    ->leftJoin('mp', 'processings', 'p', 'p.id = mp.processing_id')
    ->executeQuery()
    ->fetchAllAssociative();
```

---

## Кеш

Кеш описан отдельно в `docs/rules/cache.md`.

В Fetcher с кешем:

- добавить `CACHE_TTL`
- добавить `public const string CACHE_TAG`
- использовать `Cacher`
- tag строить через `CacheKey::tag(self::CACHE_TAG, [...])`
- key строить через `CacheKey::byClass($tag, $modelClass)`
- сохранять результат через `Cacher::setTagged($key, $result, self::CACHE_TTL, [$tag])`
- удалять tagged cache через `Cacher::deleteTag('{entity}_by_id_' . $id)`
- если Fetcher сохраняет прямой key через `Cacher::set()`, удалять через `Cacher::delete($key)`

Fetcher без кеша не получает `Cacher`.
