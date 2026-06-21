# Фронт: Processing

Модуль управления обработками изделий (люверсы, проклейка, резка и т.д.). Включает в себя управление основными данными обработки и связанными изображениями.

---

## Базовые вещи

- **Base URL:** `{BACKEND_BASE_URL}/v1`
- **Content-Type:** `application/json` для запросов с телом (кроме загрузки файлов)
- **Успех с данными:** `{ "data": { ... } }`
- **Ошибка домена (409):** `{ "error": { "code": number, "message": string } }`
- **Ошибка валидации (422):** `{ "validations": [{ "field": string, "message": string }] }`
- **401** — невалидный/отсутствующий JWT

---

## 1. Получение списка обработок (GetProcessingsAction)

**Цель:** Получить список всех доступных обработок с поддержкой пагинации и поиска.

**Запрос:**
- Метод: `GET`
- URL: `/v1/processings`
- Заголовок: `Authorization: Bearer <access_token>`
- Параметры (Query):
    - `page` — номер страницы (default: 1)
    - `perPage` — элементов на странице (default: 20)
    - `search` — поиск по названию

**Успех (200):**
```json
{
  "data": {
    "count": 1,
    "items": [
      {
        "id": 1,
        "name": "Люверсы",
        "description": "Описание обработки",
        "type": {
          "id": 1,
          "label": "метр квадратный по всей площади"
        },
        "cost_price": "10.00",
        "price": "15.00",
        "images": [
            {
                "id": 1,
                "path": "https://s3.example.com/processing/uuid.jpg",
                "alt": "Описание фото"
            }
        ]
      }
    ]
  }
}
```

---

## 2. Получение обработки по ID (GetProcessingByIdAction)

**Цель:** Получить детальную информацию о конкретной обработке.

**Запрос:**
- Метод: `GET`
- URL: `/v1/processings/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Люверсы",
    "description": "Описание обработки",
    "type": {
      "id": 1,
      "label": "метр квадратный по всей площади"
    },
    "cost_price": "10.00",
    "price": "15.00",
    "images": [
      {
        "id": 1,
        "path": "https://s3.example.com/processing/uuid.jpg",
        "alt": "Описание фото"
      }
    ]
  }
}
```

---

## 3. Получение типов обработки (GetProcessingTypesAction)

**Цель:** Получить список всех возможных типов расчета стоимости обработки.

**Запрос:**
- Метод: `GET`
- URL: `/v1/processings/types`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "label": "метр квадратный по всей площади" },
    { "id": 2, "label": "метр погонный по всему периметру" }
  ]
}
```

---

## 4. Получение обработок для селекта (GetProcessingSelectAction)

**Цель:** Получить облегченный список обработок для селектов и автокомплитов.

**Запрос:**
- Метод: `GET`
- URL: `/v1/processings/select`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "name": "Люверсы" },
    { "id": 2, "name": "Проклейка" }
  ]
}
```

---

## 5. Создание обработки (CreateProcessingAction)

**Цель:** Создать новую обработку.

**Запрос:**
- Метод: `POST`
- URL: `/v1/processings/create`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "name": "Люверсы",
  "description": "Описание",
  "type": 1,
  "costPrice": "10.00",
  "price": "15.00"
}
```

**Успех (201):**
```json
{
  "data": {
    "success": 1
  }
}
```

---

## 6. Обновление обработки (UpdateProcessingAction)

**Цель:** Обновить данные существующей обработки.

**Запрос:**
- Метод: `PATCH`
- URL: `/v1/processings/update/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "name": "Новое название",
  "description": "Новое описание",
  "type": 1,
  "costPrice": "12.00",
  "price": "18.00"
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

## 7. Удаление обработки (DeleteProcessingAction)

**Цель:** Удалить обработку.

**Запрос:**
- Метод: `DELETE`
- URL: `/v1/processings/delete/{id}`
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

## 8. Добавление изображений (CreateProcessingImageAction)

**Цель:** Загрузить фотографии для обработки.

**Запрос:**
- Метод: `POST`
- URL: `/v1/processings/{id}/images`
- Заголовок: `Authorization: Bearer <access_token>`
- Content-Type: `multipart/form-data`

| Поле | Тип | Описание |
| :--- | :--- | :--- |
| `images[]` | File (binary) | Массив файлов изображений |
| `imageAlts[]` | String | Массив альтернативных текстов (в том же порядке) |

**Успех (201):**
```json
{
  "data": {
    "success": 1
  }
}
```

---

## 9. Обновление описания изображения (UpdateProcessingImageAction)

**Цель:** Изменить альт-текст загруженного изображения.

**Запрос:**
- Метод: `PATCH`
- URL: `/v1/processings/images/{imageId}`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "alt": "Новое описание фото"
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

## 10. Удаление изображения (DeleteProcessingImageAction)

**Цель:** Удалить изображение.

**Запрос:**
- Метод: `DELETE`
- URL: `/v1/processings/images/{imageId}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": {
    "success": 1
  }
}
```
