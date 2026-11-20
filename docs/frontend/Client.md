# Фронт: Client

Модуль клиентов: список, карточка клиента, создание, обновление, удаление, справочники типов.

---

## Базовые вещи

- **Base URL:** `{BACKEND_BASE_URL}/v1`
- **Content-Type:** `application/json`
- **Успех с данными:** `{ "data": { ... } }`
- **Успех списка:** `{ "data": { "count": number, "items": [...] } }`
- **Успех мутации:** `{ "data": { "success": 1 } }`
- **Ошибка домена (409):** `{ "error": { "code": number, "message": string } }`
- **Ошибка валидации (422):** `{ "validations": [{ "field": string, "message": string }] }`
- **401** — невалидный/отсутствующий JWT

---

## 1. Список клиентов (GetClientsAction)

**Запрос:**
- Метод: `GET`
- URL: `/v1/clients`
- Заголовок: `Authorization: Bearer <access_token>`
- Query: `page`, `perPage`, `search`

**Успех (200):**
```json
{
  "data": {
    "count": 125,
    "items": [
      {
        "id": 1,
        "old_full_name": null,
        "last_name": "Иванов",
        "first_name": "Иван",
        "middle_name": "Иванович",
        "email": "ivanov@example.com",
        "docs": { "id": 1, "label": "ЭДО" },
        "type": { "id": 1, "label": "Физическое лицо" },
        "phones": [
          { "id": 1, "type": { "id": 1, "label": "Основной" }, "phone": "89991234567" }
        ],
        "companies": [
          { "id": 1, "company_name": "ООО Вектор" }
        ],
        "created_at": "2024-04-10 12:00:00"
      }
    ]
  }
}
```

---

## 2. Получение клиента по ID (GetClientByIdAction)

**Запрос:**
- Метод: `GET`
- URL: `/v1/clients/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": {
    "id": 1,
    "old_full_name": null,
    "last_name": "Иванов",
    "first_name": "Иван",
    "middle_name": "Иванович",
    "email": "ivanov@example.com",
    "info": "Комментарий к клиенту",
    "docs": { "id": 1, "label": "ЭДО" },
    "type": { "id": 1, "label": "Физическое лицо" },
    "phones": [
      { "id": 1, "type": { "id": 1, "label": "Основной" }, "phone": "89991234567" }
    ],
    "companies": [
      { "id": 1, "company_name": "ООО Вектор" }
    ],
    "created_at": "2024-04-10 12:00:00",
    "updated_at": null
  }
}
```

---

## 3. Типы клиента (GetClientTypesAction)

**Запрос:**
- Метод: `GET`
- URL: `/v1/clients/types`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "label": "Физическое лицо" },
    { "id": 2, "label": "Юридическое лицо" }
  ]
}
```

---

## 4. Типы документов клиента (GetClientDocsTypesAction)

**Запрос:**
- Метод: `GET`
- URL: `/v1/clients/docs-types`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "label": "ЭДО" },
    { "id": 2, "label": "Доверенность или печать" },
    { "id": 3, "label": "Б/Д" }
  ]
}
```

---

## 5. Типы телефонов клиента (GetClientPhoneTypesAction)

**Запрос:**
- Метод: `GET`
- URL: `/v1/clients/phone-types`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": [
    { "id": 1, "label": "Основной" },
    { "id": 2, "label": "Дополнительный" }
  ]
}
```

---

## 6. Создание клиента (CreateClientAction)

**Запрос:**
- Метод: `POST`
- URL: `/v1/clients/create`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "lastName": "Петров",
  "firstName": "Петр",
  "middleName": "Петрович",
  "email": "petrov@example.com",
  "docs": 1,
  "type": 2,
  "info": "Новый клиент",
  "phones": [
    { "type": 1, "phone": "89001112233" }
  ],
  "companies": [
    { "name": "ООО Вектор" }
  ]
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

## 7. Обновление клиента (UpdateClientAction)

**Запрос:**
- Метод: `PATCH`
- URL: `/v1/clients/update/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

```json
{
  "lastName": "Петров",
  "firstName": "Петр",
  "middleName": "Петрович",
  "email": "petrov@example.com",
  "docs": 1,
  "type": 2,
  "info": "Комментарий",
  "phones": [
    { "id": 1, "type": 1, "phone": "89001112233" },
    { "type": 2, "phone": "89008889900" }
  ],
  "companies": [
    { "id": 5, "name": "ООО Вектор Плюс" }
  ]
}
```

**Синхронизация:**
- `id` есть — запись обновляется
- `id` нет — запись создается
- записи, которых нет в запросе, удаляются

**Успех (200):**
```json
{
  "data": {
    "success": 1
  }
}
```

---

## 8. Удаление клиента (DeleteClientAction)

**Запрос:**
- Метод: `DELETE`
- URL: `/v1/clients/delete/{id}`
- Заголовок: `Authorization: Bearer <access_token>`

**Успех (200):**
```json
{
  "data": {
    "success": 1
  }
}
```
