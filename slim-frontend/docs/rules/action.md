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

Form Action читает body через `FormData`, вызывает Handler и возвращает результат через responder.

```php
final readonly class Create{Entity}Controller implements RequestHandlerInterface
{
    public function __construct(
        private Create{Entity}Handler $handler,
        private {Entity}CommandResponder $responder,
        private CsrfToken $csrf,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = FormData::fromRequest($request);
        $error = null;
        $result = null;

        try {
            $this->csrf->validate('{entities}.create', FormData::string($data, '_csrf_token'));
            $result = $this->handler->handle(new Create{Entity}Command(
                name: FormData::requiredString($data, 'name'),
            ));
        } catch (FormValidationException $exception) {
            $error = $exception->getMessage();
        } catch (ApiException $exception) {
            $error = $exception->getMessage();
        }

        return $this->responder->respond(
            request: $request,
            action: [
                'title' => 'Create entity',
                'description' => 'Result of the entity command handler.',
                'method' => 'POST',
                'endpoint' => '/{entities}/create',
            ],
            product: $result,
            delete: null,
            error: $error,
            status: $error === null ? 200 : 502,
        );
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
- Form Action читает body через `App\Components\Http\Form\FormData`.
- Form Action возвращает JSON через responder при `Accept: application/json`.
- HTML fallback формы возвращает redirect к widget-якорю.
- Action возвращает `ResponseInterface`.
- Form Action валидирует CSRF и входные данные до Handler.
- Сборка страницы находится в Unifier.
- Write-сценарий находится в Handler.
