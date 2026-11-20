# Фронт: Order (заказы)

Модуль управления заказами. Заказ включает шапку (`client`, `manager`, `designer`, `status`, `storage`, даты), а также вложенные сущности:

- `delivery` — доставка заказа
- `files` — файлы заказа
- `items` — обычные позиции заказа
- `millings` — позиции фрезеровки
- `payments` — оплаты
- `sections` — секции хранения
- `services` — услуги заказа
- `notifications` — автоматические уведомления

Итоговая цена заказа в ответах приходит как поле `price` и **считается на backend как сумма `services[].price`**. В самой сущности `Order` это поле не хранится.

---

## Базовые вещи

- **Base URL:** `{BACKEND_BASE_URL}/v1`
- **Защита:** все эндпоинты требуют заголовок `Authorization: Bearer <access_token>`
- **Content-Type:**  
  - `application/json` — для `GET` и простых тел  
  - `multipart/form-data` — для `create/update`, если участвуют файлы заказа
- **Успех с данными:** `{ "data": { ... } }`
- **Успех со списком:** `{ "data": { "count": number, "items": [...] } }`
- **Успех без payload:** `{ "data": { "success": 1 } }`
- **Ошибка домена:** `{ "error": { "code": number, "message": string } }`
- **Ошибка валидации (422):** `{ "validations": [{ "field": string, "message": string }] }`
- **401** — невалидный/отсутствующий JWT
- **403** — нет прав на изменение

---

## 1. Список заказов (GetOrdersAction)

**Цель:** получить список заказов с пагинацией, поиском и фильтрами.

**Запрос:**

- Метод: `GET`
- URL: `/v1/orders`
- Заголовок: `Authorization: Bearer <access_token>`

**Query-параметры:**

- `page` — номер страницы (default: `1`)
- `perPage` — размер страницы (default: `20`, max: `100`)
- `search` — поиск по клиенту
- `dateFrom` — начало периода в формате `YYYY-MM-DD`
- `dateTo` — конец периода в формате `YYYY-MM-DD`
- `printId` — фильтр по типу печати
- `materialId` — фильтр по материалу (только по `items`)
- `optionId` — фильтр по опции материала (только по `items`)
- `docs` — фильтр по типу документа клиента
- `managerId` — фильтр по менеджеру
- `designerId` — фильтр по дизайнеру
- `statusType` — фильтр по статусу
- `storageType` — фильтр по складу
- `serviceType` — фильтр по услуге
- `archived` — `true`, чтобы показать архивные заказы
- `deleted` — `true`, чтобы показать удалённые заказы

**Важно по датам:** фильтр работает **по пересечению интервалов** заказа и выбранного диапазона:

- заказ попадёт в выборку, если `accepted_at .. deadline_at` пересекается с `dateFrom .. dateTo`
- пример: заказ `2025-12-25 -> 2026-01-05` попадёт в диапазон `2026-01-01 -> 2026-01-10`
- пример: заказ `2026-01-08 -> 2026-01-16` тоже попадёт в тот же диапазон

**Важно по архиву/удалению:**

- по умолчанию список показывает только активные заказы (`archived_at = null`, `deleted_at = null`)
- если передать `archived=true`, будут выбраны архивные
- если передать `deleted=true`, будут выбраны удалённые

**Успех (200):**

```json
{
  "data": {
    "count": 2,
    "items": [
      {
        "id": 101,
        "creator_id": 5,
        "manager_id": 8,
        "designer_id": 12,
        "client_id": 3,
        "client": {
          "id": 3,
          "old_full_name": null,
          "last_name": "Иванов",
          "first_name": "Иван",
          "middle_name": "Иванович",
          "name": "Иванов Иван Иванович",
          "email": "client@example.com",
          "info": null,
          "docs": { "id": 1, "label": "Паспорт" },
          "type": { "id": 1, "label": "Физ. лицо" },
          "phones": [
            {
              "id": 4,
              "type": { "id": 1, "label": "Основной" },
              "phone": "+7 999 111-22-33"
            }
          ],
          "companies": []
        },
        "status_type": { "id": 2, "label": "..." },
        "storage_type": { "id": 1, "label": "НД" },
        "general_note": "Срочный заказ",
        "extension": "cdr",
        "created_at": "2026-05-10 12:00:00",
        "accepted_at": "2026-05-11 10:00:00",
        "deadline_at": "2026-05-15 18:00:00",
        "delivery": {
          "id": 1,
          "delivery_type": { "id": 1, "label": "Курьер" },
          "address": "Красноярск, ул. Ленина, 1",
          "comment": "Позвонить заранее"
        },
        "files": [
          {
            "id": 7,
            "disk_path": "orders/101/files/abc123.pdf",
            "file_name": "abc123.pdf",
            "original_name": "Макет.pdf",
            "created_at": "2026-05-10 12:01:00"
          }
        ],
        "items": [
          {
            "id": 10,
            "source_item_id": null,
            "print_id": 2,
            "product_id": 4,
            "material_id": 6,
            "option_id": 11,
            "dpi_type": { "id": 1, "label": "360 dpi" },
            "variant_type": { "id": 2, "label": "4+0" },
            "width": "2.50",
            "height": "1.20",
            "quantity": 1,
            "performer_id": 17,
            "note": "Основная позиция",
            "printed": false,
            "ready": false,
            "price": "3500.00",
            "processings": [
              {
                "id": 50,
                "processing_id": 3
              }
            ]
          }
        ],
        "millings": [],
        "payments": [],
        "sections": [
          {
            "id": 3,
            "section_type": { "id": 1, "label": "Секция 1" }
          }
        ],
        "services": [
          {
            "id": 15,
            "service_type": { "id": 1, "label": "Печать" },
            "price": "3500.00",
            "note": null
          }
        ],
        "notifications": [],
        "price": "3500.00"
      }
    ]
  }
}
```

**На фронте:** список уже приходит в унифицированном виде с вложенными коллекциями. Для таблицы можно брать только нужные поля (`id`, `client_id`, `status_type`, `price` и т.д.), но детальная структура уже доступна.

---

## 2. Заказ по ID (GetOrderByIdAction)

**Цель:** получить полную карточку заказа для экрана просмотра/редактирования.

**Запрос:**

- Метод: `GET`
- URL: `/v1/orders/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**

Структура ответа такая же, как у одного элемента списка в §1.

**Важно:** `GET /orders/{id}` открывает **любой** заказ, в том числе архивный или удалённый. Это сделано специально, чтобы фронт мог открывать старые записи.

---

## 3. Создание заказа (CreateOrderAction)

**Цель:** создать заказ вместе с вложенными сущностями.

**Запрос:**

- Метод: `POST`
- URL: `/v1/orders/create`
- Заголовок: `Authorization: Bearer <access_token>`
- `Content-Type: multipart/form-data`

**Поля формы:**

- `clientId` — обязательный ID клиента
- `managerId` — ID менеджера или `null`
- `designerId` — ID дизайнера или `null`
- `statusType` — ID статуса заказа
- `storageType` — ID склада
- `acceptedAt` — дата постановки (datetime)
- `deadlineAt` — дата сдачи (datetime)
- `generalNote` — общий комментарий (опционально)
- `extension` — расширение исходного файла/проекта (опционально)
- `delivery` — объект доставки или `null`
- `items` — массив обычных позиций
- `millings` — массив позиций фрезеровки
- `payments` — массив оплат
- `sections` — массив секций хранения
- `services` — массив услуг
- `files[]` — бинарные файлы заказа
- `fileOriginalNames[]` — необязательные отображаемые имена файлов в том же порядке

**Пример тела (логически):**

```json
{
  "clientId": 3,
  "managerId": 8,
  "designerId": 12,
  "statusType": 1,
  "storageType": 1,
  "acceptedAt": "2026-05-11 10:00:00",
  "deadlineAt": "2026-05-15 18:00:00",
  "generalNote": "Срочный заказ",
  "extension": "cdr",
  "delivery": {
    "deliveryType": 1,
    "address": "Красноярск, ул. Ленина, 1",
    "comment": "Позвонить заранее"
  },
  "items": [
    {
      "printId": 2,
      "productId": 4,
      "materialId": 6,
      "optionId": 11,
      "dpiType": 1,
      "variantType": 2,
      "width": "2.50",
      "height": "1.20",
      "quantity": 1,
      "price": "3500.00",
      "performerId": 17,
      "note": "Основная позиция",
      "printed": false,
      "ready": false,
      "processings": [
        { "processingId": 3 }
      ]
    }
  ],
  "millings": [
    {
      "printId": 5,
      "material": "ПВХ 5 мм",
      "price": "1200.00",
      "performerId": 21,
      "note": "Фрезеровка",
      "printed": false,
      "ready": false
    }
  ],
  "payments": [
    {
      "clientId": 3,
      "operationType": 2,
      "paymentType": 1,
      "reason": "Предоплата",
      "note": null,
      "confirmation": true
    }
  ],
  "sections": [
    { "sectionType": 1 }
  ],
  "services": [
    { "serviceType": 1, "price": "3500.00", "note": null },
    { "serviceType": 5, "price": "500.00", "note": "Доставка по городу" }
  ]
}
```

**Файлы:**

- `files[]` отправляются как бинарные поля `multipart/form-data`
- `fileOriginalNames[]` — опциональный строковый массив
- если `fileOriginalNames[]` не передан, backend возьмёт имя из самого upload-файла

**Успех (201):**

```json
{
  "data": {
    "success": 1
  }
}
```

**Важно:**

- create-эндпоинт не возвращает `id` созданного заказа
- если после создания нужно открыть карточку, это нужно учитывать на фронте отдельным сценарием

---

## 4. Обновление заказа (UpdateOrderAction)

**Цель:** изменить шапку заказа и синхронизировать вложенные коллекции.

**Запрос:**

- Метод без новых файлов: `PATCH`
- Метод с новыми `files[]`: `POST`
- URL: `/v1/orders/update/{id}`
- Заголовок: `Authorization: Bearer <access_token>`
- `Content-Type: application/json` — для сохранения без новых файлов
- `Content-Type: multipart/form-data` — для сохранения с новыми файлами

**Важно:** для multipart-обновления используется `POST /update/{id}` на тот же action, потому что PHP/Slim корректно разбирает `multipart/form-data` из `POST`, а `PATCH multipart/form-data` может приходить в action с пустым parsed body.

**Общие правила синхронизации:**

- если во вложенном объекте передан `id`, backend пытается обновить существующую запись
- если `id` нет — создаётся новая запись
- элементы, которые раньше были в БД, но отсутствуют в актуальном массиве, будут удалены

Это касается:

- `items`
- `millings`
- `payments`
- `sections`
- `services`
- `delivery` (одиночный объект)

**Отдельно про файлы:**

Для файлов действует специальная логика, чтобы старые файлы не удалялись случайно.

- `files[]` — только **новые** upload-файлы
- `keepFileIds[]` — список уже существующих файлов, которые нужно оставить
- если `keepFileIds` **вообще не передан**, backend по умолчанию оставляет все существующие файлы заказа
- если `keepFileIds` передан, backend оставит только перечисленные старые файлы и новые `files[]`

**Это важно для фронта:**

- обычное сохранение формы без работы с файлами безопасно
- для явного удаления старых файлов фронт должен передать обновлённый `keepFileIds`

**Пример:**

```json
{
  "clientId": 3,
  "managerId": 8,
  "designerId": 12,
  "statusType": 2,
  "storageType": 1,
  "acceptedAt": "2026-05-11 10:00:00",
  "deadlineAt": "2026-05-16 18:00:00",
  "generalNote": "Обновлённый комментарий",
  "extension": "cdr",
  "delivery": {
    "id": 1,
    "deliveryType": 1,
    "address": "Красноярск, ул. Ленина, 2",
    "comment": "Новый адрес"
  },
  "keepFileIds": [7, 9],
  "items": [
    {
      "id": 10,
      "printId": 2,
      "productId": 4,
      "materialId": 6,
      "optionId": 11,
      "dpiType": 1,
      "variantType": 2,
      "width": "2.50",
      "height": "1.20",
      "quantity": 2,
      "price": "7000.00",
      "processings": [
        { "id": 50, "processingId": 3 }
      ]
    }
  ],
  "millings": [],
  "payments": [],
  "sections": [
    { "id": 3, "sectionType": 2 }
  ],
  "services": [
    { "id": 15, "serviceType": 1, "price": "7000.00", "note": null }
  ]
}
```

**Успех (200):**

```json
{
  "data": {
    "success": 1
  }
}
```

---

## 5. Удаление заказа (DeleteOrderAction)

**Цель:** полностью удалить заказ и связанные сущности.

**Запрос:**

- Метод: `DELETE`
- URL: `/v1/orders/delete/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**

```json
{
  "data": {
    "success": 1
  }
}
```

---

## 6. Файлы заказа

### Скачать файл заказа

**Запрос:**

- Метод: `GET`
- URL: `/v1/orders/files/{id}/download`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**

Ответ — бинарное содержимое файла с заголовком `Content-Disposition: attachment`.

Фронт должен скачивать файл через API-клиент как `blob`, а не открывать временную ссылку Яндекс.Диска напрямую.

### Удалить файл заказа

**Запрос:**

- Метод: `DELETE`
- URL: `/v1/orders/files/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**

```json
{
  "data": {
    "success": 1
  }
}
```

Удаление убирает файл из БД и удаляет объект на Яндекс.Диске.

---

## 7. Enum-справочники заказа

Все ответы имеют формат:

```json
{
  "data": [
    { "id": 1, "label": "..." }
  ]
}
```

Все запросы требуют `Authorization: Bearer <access_token>`.

| Назначение | Метод | URL |
|---|---|---|
| Статусы заказа | `GET` | `/v1/orders/status-types` |
| Склады заказа | `GET` | `/v1/orders/storage-types` |
| Типы доставки | `GET` | `/v1/orders/delivery-types` |
| Типы операций оплаты | `GET` | `/v1/orders/payment-operation-types` |
| Типы оплаты | `GET` | `/v1/orders/payment-types` |
| Секции хранения | `GET` | `/v1/orders/section-types` |
| Типы услуг | `GET` | `/v1/orders/service-types` |

**Важно по статусам:**

- `/v1/orders/status-types` — role-aware endpoint
- список статусов может зависеть от роли текущего пользователя
- фронт не должен хардкодить полный список статусов, а должен брать его из API

---

## 8. Поля вложенных сущностей

### `delivery`

```json
{
  "id": 1,
  "deliveryType": 1,
  "address": "Строка или null",
  "comment": "Строка или null"
}
```

### `files`

В ответе:

```json
{
  "id": 7,
  "disk_path": "orders/101/files/abc123.pdf",
  "file_name": "abc123.pdf",
  "original_name": "Макет.pdf",
  "created_at": "2026-05-10 12:01:00"
}
```

### `items`

```json
{
  "id": 10,
  "sourceItemId": null,
  "printId": 2,
  "productId": 4,
  "materialId": 6,
  "optionId": 11,
  "dpiType": 1,
  "variantType": 2,
  "width": "2.50",
  "height": "1.20",
  "quantity": 1,
  "price": "3500.00",
  "performerId": 17,
  "note": "Комментарий",
  "printed": false,
  "ready": false,
  "processings": [
    { "id": 50, "processingId": 3 }
  ]
}
```

**`sourceItemId`:**

- `null` — обычная позиция
- число — позиция создана как брак/дубликат другой позиции заказа

### `millings`

```json
{
  "id": 20,
  "sourceItemId": null,
  "printId": 5,
  "material": "ПВХ 5 мм",
  "price": "1200.00",
  "performerId": 21,
  "note": "Комментарий",
  "printed": false,
  "ready": false
}
```

### `payments`

```json
{
  "id": 30,
  "clientId": 3,
  "operationType": 2,
  "paymentType": 1,
  "reason": "Предоплата",
  "note": null,
  "confirmation": true
}
```

### `sections`

```json
{
  "id": 3,
  "sectionType": 1
}
```

### `services`

```json
{
  "id": 15,
  "serviceType": 1,
  "price": "3500.00",
  "note": null
}
```

### `notifications`

`notifications` приходят только в ответах. Это служебная история автоматических событий.

```json
{
  "id": 99,
  "notification_type": { "id": 1, "label": "Отправлен на производство" },
  "created_at": "2026-05-10 12:30:00"
}
```

---

## 9. Практические замечания для фронта

- Для enum-полей всегда отправляйте **числовой `id`**, а не `label`.
- Для денежных значений (`price`) отправляйте строки (`"100.00"`), а не числа JS.
- Для `update` по коллекциям лучше всегда отправлять **полное актуальное состояние** массива.
- Для файлов на `update`:
  - новые файлы — в `files[]`
  - оставляемые старые — в `keepFileIds[]`
- `disk_path` и `file_name` — внутренние технические поля backend / Яндекс.Диска; для UI пользователю обычно нужен `original_name`.
