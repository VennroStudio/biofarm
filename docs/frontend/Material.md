# Фронт: Material (материалы)

Справочник материалов: CRUD, вложенные варианты (`options`) с ценами по площади/поштучно/резу и связями с обработками, изображения. Все эндпоинты защищены JWT; операции с данными разрешены ролям по бизнес-правилам на бэкенде (см. матрицу прав).

---

## Базовые вещи

- **Base URL:** `{BACKEND_BASE_URL}/v1`
- **Content-Type:** `application/json` для запросов с телом (кроме загрузки файлов)
- **Защита:** заголовок `Authorization: Bearer <access_token>`
- **Успех с данными:** `{ "data": { ... } }` или `{ "data": [ ... ] }` (для справочников enum — массив в `data`)
- **Успех без payload:** `{ "data": { "success": 1 } }`
- **Ошибка домена (409):** `{ "error": { "code": number, "message": string } }`
- **Ошибка валидации (422):** `{ "validations": [{ "field": string, "message": string }] }`
- **401** — невалидный/отсутствующий JWT
- **403** — нет прав на операцию

**Подсказка по формам:** для полей с числовыми типами (`pricingType`, `dpiType`, `areaRangeType`, `variantType`, тип реза и т.д.) подставляйте `id` из соответствующих GET-справочников ниже. Денежные поля в JSON — **строки** в формате десятичного числа, проходят формат `^\d+(\.\d{1,2})?$` (например: `"10"`, `"10.5"`, `"10.50"`).

---

## 1. Список материалов (GetMaterialsAction)

**Цель:** пагинированный список с поиском по названию. В ответе — `id`, `name`, `description`, список изображений (без дерева вариантов и цен).

**Запрос:**
- Метод: `GET`
- URL: `/v1/materials`
- Заголовок: `Authorization: Bearer <access_token>`

**Query-параметры:**
- `page` — номер страницы (default: `1`)
- `perPage` — размер страницы (default: `20`)
- `search` — подстрока в поле `name` (опционально)

**Успех (200):**
```json
{
  "data": {
    "count": 42,
    "items": [
      {
        "id": 1,
        "name": "Баннер",
        "description": "Описание материала",
        "images": [
          {
            "id": 1,
            "path": "https://s3.example.com/material/uuid.jpg",
            "alt": "Описание фото"
          }
        ]
      }
    ]
  }
}
```

---

## 2. Материал по ID (GetMaterialByIdAction)

**Цель:** карточка материала для отображения/редактирования шапки и картинок. Вложенные варианты и цены для полноценного редактора подгружаются отдельными query к модулям Material (опции, прайсинг и т.д.), если они используются на фронте.

**Запрос:**
- Метод: `GET`
- URL: `/v1/materials/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Баннер",
    "description": "Описание материала",
    "images": [
      {
        "id": 1,
        "path": "https://s3.example.com/material/uuid.jpg",
        "alt": "Описание фото"
      }
    ]
  }
}
```

---

## 3. Справочники enum (типы полей)

Все ответы: **200**, тело `{ "data": [ { "id": <number>, "label": "<string>" }, ... ] }`. Список берите из `data` для селектов.

| Цель | Action | Метод | URL |
|------|--------|------|-----|
| Тип ценообразования варианта (по площади / поштучно) | `GetMaterialOptionPricingTypesAction` | `GET` | `/v1/materials/option-pricing-types` |
| Диапазон площади (цена по площади) | `GetMaterialAreaRangeTypesAction` | `GET` | `/v1/materials/area-range-types` |
| DPI (цена по площади) | `GetMaterialDpiTypesAction` | `GET` | `/v1/materials/dpi-types` |
| Вариант печати (поштучно) | `GetMaterialVariantTypesAction` | `GET` | `/v1/materials/variant-types` |
| Тип реза | `GetMaterialPricingCutTypesAction` | `GET` | `/v1/materials/pricing-cut-types` |

**Запрос (одинаковый для всех пяти):**
- Заголовок: `Authorization: Bearer <access_token>`

**Пример успеха (200):**
```json
{
  "data": [
    { "id": 1, "label": "По площади" },
    { "id": 2, "label": "Поштучно" }
  ]
}
```

**На фронте:** кэшировать справочники на сессию экрана; `id` совпадают с значениями в `Create` / `Update` (поля `pricingType`, `dpiType`, `areaRangeType`, `variantType`, `type` в `pricingByCut`).

---

## 4. Материалы для селекта (GetMaterialSelectAction)

**Цель:** облегченный список материалов для селектов и автокомплитов.

**Запрос:**
- Метод: `GET`
- URL: `/v1/materials/select`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "name": "Баннер" },
    { "id": 2, "name": "Пленка" }
  ]
}
```

---

## 5. Варианты материала для селекта (GetMaterialOptionSelectAction)

**Цель:** получить список вариантов конкретного материала для селекта.

**Запрос:**
- Метод: `GET`
- URL: `/v1/materials/{id}/options/select`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 10, "name": "Глянец 340" },
    { "id": 11, "name": "Матовый 440" }
  ]
}
```

---

## 6. Создание материала (CreateMaterialAction)

**Цель:** создать материал, опционально сразу с вариантами и вложенными строками цен/связей.

**Запрос:**
- Метод: `POST`
- URL: `/v1/materials/create`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "name": "Баннер",
  "description": "Описание",
  "options": [
    {
      "name": "Глянец 340",
      "pricingType": 1,
      "isCut": false,
      "pricingByArea": [
        {
          "dpiType": 1,
          "areaRangeType": 1,
          "price": "100.00",
          "cost": "50.00",
          "printHours": "1.50"
        }
      ],
      "pricingByPiece": [],
      "pricingByCut": [
        { "type": 1, "price": "10.00" }
      ],
      "processings": [
        { "processingId": 3 }
      ]
    }
  ]
}
```

**Поля тела:**
- `name` — обязателен, длина `2..255`; `description` — опционально, максимум `65535`.
- `options` — опциональный массив вариантов. У каждого варианта: `name` (не пустой, до `255` символов), `pricingType` (enum из §3, допустимы значения `1..2`), `isCut` (по умолчанию `false`). Для **новой** вложенной строки `id` не передают.
- `pricingByArea` — для строк: `dpiType` (`1..5`), `areaRangeType` (`1..5`), `price`, `cost`, `printHours` (строки-дроби).
- `pricingByPiece` — `variantType` (`1..3`), `price`, `cost`, `printHours`.
- `pricingByCut` — `type` (справочник «тип реза», допустимы `1..2`), `price`.
- `processings` — `processingId` (ID записи из справочника обработок, не enum Material).
- Бизнес-правила структуры:
  - если `pricingType = 1` (по площади), массив `pricingByPiece` должен быть пустым;
  - если `pricingType = 2` (поштучно), массив `pricingByArea` должен быть пустым;
  - если `isCut = false`, массив `pricingByCut` должен быть пустым;
  - внутри одного варианта не допускаются дубли:
    - `pricingByArea` по паре (`dpiType`, `areaRangeType`)
    - `pricingByPiece` по `variantType`
    - `pricingByCut` по `type`
    - `processings` по `processingId`

**Успех (201):**
```json
{ "data": { "success": 1 } }
```

**Ошибки:** `401`, `403`, `409` (ошибка бизнес-правил структуры), `422`.

**На фронте:** create-эндпоинт **не возвращает `id`** созданного материала. После успеха либо переходите в список, либо получайте нужную запись отдельным способом (например, через повторный запрос списка/поиска по имени, если это допустимо в сценарии).

---

## 7. Обновление материала (UpdateMaterialAction)

**Цель:** изменить название, описание и **полный набор** вариантов с вложенными данными (синхронизация: удаляются варианты/строки, которых нет в теле, создаются и обновляются переданные).

**Запрос:**
- Метод: `PATCH`
- URL: `/v1/materials/update/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "name": "Новое название",
  "description": "Новое описание",
  "options": [
    {
      "id": 10,
      "name": "Глянец 340",
      "pricingType": 1,
      "isCut": false,
      "pricingByArea": [
        {
          "id": 100,
          "dpiType": 1,
          "areaRangeType": 2,
          "price": "120.00",
          "cost": "60.00",
          "printHours": "2.00"
        }
      ],
      "pricingByPiece": [],
      "pricingByCut": [],
      "processings": [{ "id": 5, "processingId": 2 }]
    }
  ]
}
```

**Важно:**
- У **существующих** вариантов и вложенных строк передавайте `id`; у новых в рамках запроса — без `id`.
- Массив `options` должен отражать **желаемое итоговое состояние** (как и для телефонов/компаний в Client).
- Синхронизация применяется не только к `options`, но и к вложенным массивам `pricingByArea`, `pricingByPiece`, `pricingByCut`, `processings` внутри каждого варианта:
  - если у вложенной записи есть `id` — запись обновляется;
  - если `id` нет — создается новая;
  - записи, которых нет в итоговом массиве, будут удалены.

**Успех (200):**
```json
{ "data": { "success": 1 } }
```

**Ошибки:** `401`, `403`, `404` (материал не найден), `409` (ошибка бизнес-правил структуры), `422`.

---

## 8. Удаление материала (DeleteMaterialAction)

**Цель:** удалить материал; зависимые сущности (варианты, цены, связи, изображения в хранилище) обрабатываются на бэкенде.

**Запрос:**
- Метод: `DELETE`
- URL: `/v1/materials/delete/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{ "data": { "success": 1 } }
```

**Ошибки:** `401`, `403`, `404`, `409` (при нарушении бизнес-правил, если настроено).

---

## 9. Добавление изображений (CreateMaterialImageAction)

**Цель:** загрузить одну или несколько фотографий для материала.

**Запрос:**
- Метод: `POST`
- URL: `/v1/materials/{id}/images`
- Заголовок: `Authorization: Bearer <access_token>`
- Content-Type: `multipart/form-data`

| Поле | Тип | Описание |
| :--- | :--- | :--- |
| `images[]` | File (binary) | Массив файлов изображений |
| `imageAlts[]` | String | Массив альтернативных текстов (в том же порядке) |

**Успех (201):**
```json
{ "data": { "success": 1 } }
```

---

## 10. Обновление описания изображения (UpdateMaterialImageAction)

**Цель:** изменить альт-текст загруженного изображения.

**Запрос:**
- Метод: `PATCH`
- URL: `/v1/materials/images/{imageId}`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "alt": "Новое описание фото"
}
```

**Успех (200):**
```json
{ "data": { "success": 1 } }
```

---

## 11. Удаление изображения (DeleteMaterialImageAction)

**Цель:** удалить конкретную фотографию.

**Запрос:**
- Метод: `DELETE`
- URL: `/v1/materials/images/{imageId}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{ "data": { "success": 1 } }
```
