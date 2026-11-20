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

Form Action читает body через `FormData`, валидирует CSRF, вызывает Handler и возвращает redirect или рендер Twig.

```php
final readonly class Create{Entity}Controller implements RequestHandlerInterface
{
    public function __construct(
        private Create{Entity}Handler $handler,
        private CsrfToken $csrf,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = FormData::fromRequest($request);

        try {
            $this->csrf->validate('{entities}.create', FormData::string($data, '_csrf_token'));
            $this->handler->handle(new Create{Entity}Command(
                name: FormData::requiredString($data, 'name'),
            ));
        } catch (FormValidationException) {
            return $this->redirectBack();
        }

        return $this->redirectBack();
    }

    private function redirectBack(): ResponseInterface
    {
        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', '/{page}#{widget}');
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
- Action рендерит Twig через `HtmlResponder` или возвращает redirect после формы.
- Form Action читает body через `App\Components\Http\Form\FormData`.
- HTML-форма отправляется обычным POST в web-контроллер.
- Action возвращает `ResponseInterface`.
- Form Action валидирует CSRF и входные данные до Handler.
- Сборка страницы находится в Unifier.
- Write-сценарий находится в Handler.
