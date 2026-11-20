# Cache

Кеш используется в Fetcher'ах, где чтение часто повторяется и результат можно безопасно переиспользовать.

**Расположение:**
- компонент: `src/Components/Cacher/`
- использование в Fetcher: `src/Modules/{Module}/Query/{Entity}/{Action}/`

---

## Правило

Кеш удаляется тем же способом, каким был сохранен.

- `Cacher` читает, пишет и удаляет кеш
- `Cacher::set()` сохраняет прямой key
- `Cacher::delete()` удаляет прямой key
- `Cacher::setTagged()` сохраняет key и привязывает его к tag
- `Cacher::deleteTag()` удаляет все keys по tag
- `CacheKey::tag()` строит tag данных
- `CacheKey::byClass()` строит key конкретной формы ответа
- Handler сам удаляет cache своей бизнес-логики через `Cacher`

---

## Fetcher

### Direct key

Используется, когда у query один стабильный ответ.

```php
$key = '{entity}_by_id_' . $query->id;

/** @var {Entity}ModelInterface|null $cached */
$cached = $this->cacher->get($key);

if ($cached !== null) {
    return $cached;
}

$result = {Entity}Model::fromRow($row);
$this->cacher->set($key, $result, self::CACHE_TTL);

return $result;
```

Удаление:

```php
$this->cacher->delete('{entity}_by_id_' . $id);
```

### Tagged key

Используется, когда у одних данных может быть несколько форм ответа.

```php
use App\Components\Cacher\CacheKey;
use App\Components\Cacher\Cacher;

private const int CACHE_TTL = 900;
public const string CACHE_TAG = '{entity}_by_id';

public function __construct(
    private Connection $connection,
    private Cacher $cacher,
) {}
```

```php
$tag = CacheKey::tag(self::CACHE_TAG, [$query->id]);
$key = CacheKey::byClass($tag, $modelClass);

/** @var T|null $cached */
$cached = $this->cacher->get($key);

if ($cached !== null) {
    return $cached;
}

$result = $modelClass::fromRow($row);
$this->cacher->setTagged($key, $result, self::CACHE_TTL, [$tag]);

return $result;
```

Tag описывает данные и совпадает с тем, что удаляется в Handler:

```php
{entity}_by_id_10
```

Key описывает конкретную форму ответа:

```php
{entity}_by_id_10.{Entity}Details
{entity}_by_id_10.{Entity}IdName
```

---

## Handler

Handler выбирает `delete()` или `deleteTag()` по тому, как Fetcher сохраняет кеш.

```php
use App\Components\Cacher\Cacher;

$this->cacher->delete('{entity}_by_id_' . $id);
```

```php
$this->cacher->deleteTag('{entity}_by_id_' . $id);
```

Если Handler меняет связанные данные, он удаляет весь cache, за который отвечает.

```php
public function handle(Update{Entity}Command $command): void
{
    // ...

    $this->cacher->delete('{entity}_by_id_' . $command->id);
}
```

```php
public function handle(Update{Entity}PriceCommand $command): void
{
    // ...

    $this->cacher->deleteTag(
        '{entity}_price_by_parent_id_' . $command->parentId . '_child_id_' . $command->childId
    );
}
```
