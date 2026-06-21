# Action — HTTP-обработчик

Точка входа HTTP: получить данные из запроса, собрать Command/Query, провалидировать, вызвать Handler/Fetcher и вернуть Response.

**Расположение:**
- Action: `src/Http/Action/v1/{Module}/{Action}{Entity}Action.php`
- Маршруты: `config/routes/v1.php`

---

## Состав Action

Action собирается только из тех блоков, которые нужны конкретному endpoint.

- OpenAPI атрибут
- `final readonly class`
- `RequestHandlerInterface`
- зависимости через конструктор
- `handle(ServerRequestInterface $request): ResponseInterface`
- получение route/query/body/file/cookie данных
- `denormalize()` и `validate()`
- вызов Handler или Fetcher
- сборка Response

В Action не пишется бизнес-логика. Проверки доступа, изменение сущностей, работа с БД и внешними API остаются в Handler/Fetcher/Service.

---

## Заголовок класса

```php
<?php

declare(strict_types=1);

namespace App\Http\Action\v1\{Module};

use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(
    path: '/{entities}/{id}',
    summary: 'Получить {entity}',
    security: [['bearerAuth' => []]],
    tags: ['{Modules}'],
    responses: [
        new OA\Response(response: 200, description: 'Успех'),
        new OA\Response(response: 401, description: 'Не авторизован'),
        new OA\Response(response: 404, description: 'Не найдено'),
    ]
)]
final readonly class Get{Entity}ByIdAction implements RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }
}
```

---

## Данные запроса

- route params: `Route::getArgumentToInt($request, 'id')`
- query params: `$request->getQueryParams()`
- body: `(array) $request->getParsedBody()`
- current user: `RequestIdentity::get($request)`
- file: `RequestFile::extract($request, 'file')`
- cookies: `RequestCookies::get($request)`

Дополнительные поля из route и identity добавляются в Command через `array_merge()`.

Имена входных JSON-полей совпадают с полями Command/Query. Ответ API собирается через ReadModel/Unifier, ключи ответа описаны в [ReadModel](readmodel.md).

---

## Responses

| Класс | Когда |
|-------|-------|
| `JsonDataResponse($data)` | Один объект, справочник, token response |
| `JsonDataItemsResponse(count: $count, items: $items)` | Список с количеством |
| `JsonDataSuccessResponse()` | Успех создания, статус `201` |
| `JsonDataSuccessResponse(1, 200)` | Успех update/delete, статус `200` |
| `new Response(204)` | Успех без JSON, например logout |

---

## GET один объект

Fetcher ищет данные, Unifier собирает ответ.

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $id = Route::getArgumentToInt($request, 'id');

    $item = $this->fetcher->fetch(new {Entity}GetByIdQuery($id));

    return new JsonDataResponse(
        $this->unifier->unifyOne(null, $item)
    );
}
```

---

## GET список

Список всегда возвращается через `JsonDataItemsResponse`: `count` и `items`.

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $query = $this->denormalizer->denormalize(
        $request->getQueryParams(),
        {Entity}FindAllQuery::class,
    );

    $this->validator->validate($query);

    $result = $this->fetcher->fetch($query);

    return new JsonDataItemsResponse(
        count: $result->count,
        items: $this->unifier->unify(null, $result->items),
    );
}
```

---

## GET select

Select endpoint возвращает легкий список ReadModel без Unifier, если не нужно подмешивать дополнительные данные.

```php
use App\Components\ReadModel\ReadModelArray;

public function handle(ServerRequestInterface $request): ResponseInterface
{
    return new JsonDataResponse(
        ReadModelArray::fromItems(
            $this->fetcher->fetch(new {Entity}GetBySelectQuery())
        )
    );
}
```

Если у select есть фильтры, сначала собирается Query из `$request->getQueryParams()` и валидируется.

---

## GET enum

Enum-класс описывается отдельно в [Enum](enum.md). В Action показывается только отдача справочника.

```php
use App\Components\Enum\EnumModel;

public function handle(ServerRequestInterface $request): ResponseInterface
{
    return new JsonDataResponse(
        EnumModel::fromEnumClass({EnumName}::class)
    );
}
```

Если enum зависит от роли:

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);

    return new JsonDataResponse(
        EnumModel::fromEnumClassForRole({EnumName}::class, $identity->role)
    );
}
```

---

## GET UI permissions

Permission описывается отдельно в [Permission](permission.md). В Action показывается только отдача frontend map.

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);

    return new JsonDataResponse(
        $this->uiPermissionService->permissionsForRole($identity->role)
    );
}
```

---

## POST создание

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);

    $command = $this->denormalizer->denormalize(
        array_merge((array) $request->getParsedBody(), [
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ]),
        Create{Entity}Command::class,
    );

    $this->validator->validate($command);
    $this->handler->handle($command);

    return new JsonDataSuccessResponse();
}
```

---

## PATCH обновление

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);

    $command = $this->denormalizer->denormalize(
        array_merge((array) $request->getParsedBody(), [
            'entityId'        => Route::getArgumentToInt($request, 'id'),
            'currentUserId'   => $identity->id,
            'currentUserRole' => $identity->role->value,
        ]),
        Update{Entity}Command::class,
    );

    $this->validator->validate($command);
    $this->handler->handle($command);

    return new JsonDataSuccessResponse(1, 200);
}
```

---

## DELETE

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);

    $command = $this->denormalizer->denormalize([
        'entityId'        => Route::getArgumentToInt($request, 'id'),
        'currentUserId'   => $identity->id,
        'currentUserRole' => $identity->role->value,
    ], Delete{Entity}Command::class);

    $this->validator->validate($command);
    $this->handler->handle($command);

    return new JsonDataSuccessResponse(1, 200);
}
```

---

## Multipart upload

Для multipart Action получает файл через `RequestFile::extract()` и передает во входную Command только нужные данные.

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $identity = RequestIdentity::get($request);
    $file = RequestFile::extract($request, 'file');

    if ($file === null) {
        throw new DomainExceptionModule(
            module: '{module}',
            message: 'error.file_required',
            code: 1,
        );
    }

    $url = $this->handler->handle(new Upload{Entity}FileCommand(
        entityId: Route::getArgumentToInt($request, 'id'),
        currentUserId: $identity->id,
        currentUserRole: $identity->role->value,
        tmpFilePath: $file->getPath(),
    ));

    return new JsonDataResponse(['file' => $url], 200);
}
```

---

## Auth cookies

### Login / refresh

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $command = $this->denormalizer->denormalize(
        (array) $request->getParsedBody(),
        LoginCommand::class,
    );

    $this->validator->validate($command);

    $result = $this->handler->handle($command);

    $response = new JsonDataResponse([
        'access_token' => $result->accessToken,
        'expires_in'   => $result->expiresIn,
    ]);

    return $this->cookieManager->apply(
        response: $response,
        context: new CookieContext(
            refreshToken: $result->refreshToken,
            loggedIn: '1',
        ),
    );
}
```

### Logout

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $cookies = RequestCookies::get($request);

    $this->handler->handle(new LogoutCommand(
        refreshToken: $cookies->refreshToken,
    ));

    return $this->cookieManager->discard(
        new Response(204),
        new CookieContext(),
    );
}
```

---

## Unifier

Unifier описан отдельно в [Unifier](unifier.md). В Action показывается только вызов.

```php
return new JsonDataResponse(
    $this->unifier->unifyOne(null, $item)
);
```

```php
return new JsonDataItemsResponse(
    count: $result->count,
    items: $this->unifier->unify(null, $result->items),
);
```

Если endpoint возвращает select, enum, permission map или success response, Unifier не нужен.

---

## Регистрация маршрутов

Маршруты регистрируются в `config/routes/v1.php`.

```php
$group->group('/{entities}', new Group(static function (RouteCollectorProxy $group): void {
    $group->get('', Get{Entities}Action::class)->add(Authenticate::class);
    $group->get('/select', Get{Entity}SelectAction::class)->add(Authenticate::class);
    $group->post('/create', Create{Entity}Action::class)->add(Authenticate::class);
    $group->patch('/update/{id}', Update{Entity}Action::class)->add(Authenticate::class);
    $group->delete('/delete/{id}', Delete{Entity}Action::class)->add(Authenticate::class);
    $group->get('/{id}', Get{Entity}ByIdAction::class)->add(Authenticate::class);
}));
```

`Authenticate` нужен, если Action использует `RequestIdentity::get($request)`.

Для refresh/logout используется `ExtractCookies`.

```php
$group->post('/refresh', RefreshTokenAction::class)->add(ExtractCookies::class);
$group->post('/logout', LogoutAction::class)->add(ExtractCookies::class);
```
