# Action

Action — web-вход в страницу или form action.

В проекте физически лежит в `src/Http/Web` и называется Controller.

**Расположение страницы:**

```
src/Http/Web/{Page}/{Page}Controller.php
```

**Расположение form action:**

```
src/Http/Web/{Entity}/{Scenario}Controller.php
```

---

## Page Action

Page Action вызывает Unifier и рендерит Twig.

```php
final readonly class {Page}Controller implements RequestHandlerInterface
{
    public function __construct(
        private {Page}Unifier $unifier,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->twig->render('pages/{page}/index.html.twig', [
            'page' => $this->unifier->unify(),
        ]));
    }
}
```

---

## Form Action

Form Action читает body, вызывает Handler и рендерит result page.

```php
final readonly class Create{Entity}Controller implements RequestHandlerInterface
{
    public function __construct(
        private Create{Entity}Handler $handler,
        private Environment $twig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $error = null;
        $result = null;

        try {
            $result = $this->handler->handle(new Create{Entity}Command(
                name: trim((string) ($data['name'] ?? '')),
            ));
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return new HtmlResponse($this->twig->render('pages/{entity}-command/result.html.twig', [
            'result' => $result,
            'error' => $error,
        ]), $error === null ? 200 : 502);
    }
}
```

---

## Routes

```php
$group->get('/{page}', {Page}Controller::class);
$group->post('/{entities}/create', Create{Entity}Controller::class);
```

---

## Правила

- Action принимает HTTP-данные.
- Action вызывает Unifier или Handler.
- Action рендерит Twig.
- Action возвращает `HtmlResponse`.
- Сборка страницы находится в Unifier.
- Write-сценарий находится в Handler.
