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
        private HtmlResponder $html,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->html->render('pages/{page}/index.html.twig', [
            'page' => $this->unifier->unify(),
        ]);
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
        private HtmlResponder $html,
        private CsrfToken $csrf,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $error = null;
        $result = null;

        try {
            $this->csrf->validate('{entities}.create', ProductFormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new Create{Entity}Command(
                name: ProductFormData::requiredString($data, 'name'),
            ));
        } catch (FormValidationException $exception) {
            $error = $exception->getMessage();
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return $this->html->render('pages/{entity}-command/result.html.twig', [
            'result' => $result,
            'error' => $error,
        ], $error === null ? 200 : 502);
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
- Action рендерит Twig через `HtmlResponder`.
- Action возвращает `ResponseInterface`.
- Form Action валидирует CSRF и входные данные до Handler.
- Сборка страницы находится в Unifier.
- Write-сценарий находится в Handler.
