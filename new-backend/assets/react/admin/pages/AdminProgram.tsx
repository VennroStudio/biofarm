import { useEffect, useRef, useState, type FormEvent } from "react";
import { request } from "../api/client";
import {
    Section,
    Field,
    Rows,
    Pager,
    money,
    inputClass,
    buttonClass,
    type Rates,
    type Listing,
    type Row,
} from "../../program/shared";
const base = "/admin/api/program";
const value = (f: FormData, key: string) => String(f.get(key) || "");
const num = (f: FormData, key: string) => Number(f.get(key));
export function AdminProgram() {
    const [withdrawalStatus, setWithdrawalStatus] = useState("approved");
    const [tab, setTab] = useState("users");
    const [page, setPage] = useState(1);
    const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
    const [rates, setRates] = useState<Rates | null>(null);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [revision, setRevision] = useState(0);
    const [busy, setBusy] = useState(false);
    const [selected, setSelected] = useState<Row | null>(null);
    const [simulation, setSimulation] = useState<Row | null>(null);
    const [config, setConfig] = useState<{ configured: boolean; receiptsEnabled: boolean } | null>(null);
    const [orderId, setOrderId] = useState("");
    const [order, setOrder] = useState<{ payment: Row; items: Row[]; operations: Row[] } | null>(null);
    const refund = useRef<{ id: string; body: Record<string, unknown> } | null>(null);
    useEffect(() => {
        void request<Rates>(`${base}/settings`)
            .then(setRates)
            .catch((e) => setError(String(e)));
        void request<{ configured: boolean; receiptsEnabled: boolean }>("/admin/api/payments/config")
            .then(setConfig)
            .catch((e) => setError(String(e)));
    }, [revision]);
    useEffect(() => {
        if (["settings", "payments"].includes(tab)) return;
        let live = true;
        void request<Listing>(`${base}/${tab}?page=${page}`)
            .then((d) => {
                if (live) setList(d);
            })
            .catch((e) => setError(String(e)));
        return () => {
            live = false;
        };
    }, [tab, page, revision]);
    async function mutate(path: string, body: Record<string, unknown>, method = "POST") {
        setBusy(true);
        setError("");
        setNotice("");
        try {
            const result = await request<Row>(path, { method, body });
            setNotice(path.endsWith("/simulate") ? "Расчёт выполнен" : "Сохранено");
            setRevision((n) => n + 1);
            return result;
        } catch (e) {
            setError(String(e));
            throw e;
        } finally {
            setBusy(false);
        }
    }
    async function form(
        event: FormEvent<HTMLFormElement>,
        path: string,
        body: (f: FormData) => Record<string, unknown>,
        method = "POST",
    ) {
        event.preventDefault();
        try {
            await mutate(path, body(new FormData(event.currentTarget)), method);
            setSelected(null);
        } catch {
            /* Error shown above. */
        }
    }
    const columns: Record<string, [string, string][]> = {
        users: [
            ["id", "ID"],
            ["name", "Имя"],
            ["is_partner", "Партнёр"],
            ["is_referral", "Реферал"],
            ["parent_name", "Пригласивший"],
            ["referred_by_user_id", "ID пригласившего"],
        ],
        ledger: [
            ["created_at", "Дата"],
            ["user_id", "Пользователь"],
            ["wallet", "Счёт"],
            ["kind", "Операция"],
            ["state", "Состояние"],
            ["order_id", "Заказ"],
            ["amount_minor", "Сумма"],
            ["details", "Детали"],
        ],
        audit: [
            ["created_at", "Дата"],
            ["actor_id", "Администратор"],
            ["kind", "Действие"],
            ["payload", "Изменения и причина"],
        ],
        withdrawals: [
            ["id", "Заявка"],
            ["user_id", "Пользователь"],
            ["amount_minor", "Сумма"],
            ["status", "Состояние"],
            ["details", "Реквизиты"],
            ["reference", "Подтверждение"],
            ["reason", "Причина"],
        ],
    };
    return (
        <div className="space-y-6">
            <h1 className="text-3xl text-primary">Партнёрская программа</h1>
            <p>Начисления по подтверждённым оплатам. Переводы выполняются вручную.</p>
            <div className="flex flex-wrap gap-2">
                {[
                    ["users", "Пользователи и команда"],
                    ["settings", "Правила и симулятор"],
                    ["ledger", "Журнал"],
                    ["audit", "Аудит"],
                    ["withdrawals", "Выплаты"],
                    ["payments", "Оплата и возвраты"],
                ].map(([key, title]) => (
                    <button
                        className={tab === key ? buttonClass : "rounded-lg border p-2"}
                        key={key}
                        onClick={() => {
                            setTab(key);
                            setPage(1);
                            setSelected(null);
                        }}
                    >
                        {title}
                    </button>
                ))}
            </div>
            {error && (
                <p role="alert" className="rounded bg-red-50 p-3 text-red-700">
                    {error}
                </p>
            )}
            {notice && <p role="status">{notice}</p>}
            {!["settings", "payments"].includes(tab) && (
                <Section title="Записи">
                    <Rows
                        rows={list.items}
                        columns={columns[tab]}
                        actions={
                            ["users", "withdrawals"].includes(tab)
                                ? (row) => (
                                      <button className={buttonClass} onClick={() => setSelected(row)}>
                                          Управление
                                      </button>
                                  )
                                : undefined
                        }
                    />
                    <Pager page={page} setPage={setPage} hasMore={list.items.length === list.limit} />
                </Section>
            )}
            {selected && (
                <Section title={`Управление: ${selected.name || selected.id}`}>
                    <button className="underline" onClick={() => setSelected(null)}>
                        Закрыть
                    </button>
                    {tab === "users" && (
                        <form
                            className="grid gap-3"
                            onSubmit={(e) =>
                                void form(
                                    e,
                                    `${base}/users/${selected.id}`,
                                    (f) => ({
                                        parentId: value(f, "parentId") ? num(f, "parentId") : null,
                                        isPartner: f.get("isPartner") === "on",
                                        reason: value(f, "reason"),
                                    }),
                                    "PATCH",
                                )
                            }
                        >
                            <p>
                                Назначение партнёром отделит пользователя и всех потомков от прежней команды. Прошлые
                                заказы сохраняют старые снимки.
                            </p>
                            <Field name="ID пригласившего (пусто — нет)">
                                <input
                                    className={inputClass}
                                    type="number"
                                    min="1"
                                    name="parentId"
                                    defaultValue={String(selected.referred_by_user_id || "")}
                                />
                            </Field>
                            <Field name="Партнёр">
                                <input
                                    name="isPartner"
                                    type="checkbox"
                                    defaultChecked={Boolean(Number(selected.is_partner))}
                                />
                            </Field>
                            <Field name="Причина изменения">
                                <textarea className={inputClass} name="reason" required />
                            </Field>
                            <label>
                                <input type="checkbox" required /> Подтверждаю изменение дерева и отделение ветки при
                                назначении партнёром
                            </label>
                            <button className={buttonClass} disabled={busy}>
                                Сохранить
                            </button>
                        </form>
                    )}
                    {tab === "withdrawals" && (
                        <form
                            className="grid gap-3"
                            onSubmit={(e) =>
                                void form(
                                    e,
                                    `${base}/withdrawals/${selected.id}`,
                                    (f) => ({
                                        status: value(f, "status"),
                                        reference: value(f, "reference"),
                                        reason: value(f, "reason"),
                                    }),
                                    "PATCH",
                                )
                            }
                        >
                            <p>Одобрение только подтверждает заявку. «Выплачено» выбирайте после реального перевода.</p>
                            <Field name="Действие">
                                <select className={inputClass} name="status" required value={withdrawalStatus} onChange={e=>setWithdrawalStatus(e.target.value)}>
                                    <option value="approved">Одобрить без перевода</option>
                                    <option value="paid">Отметить выполненный перевод</option>
                                    <option value="rejected">Отклонить и освободить резерв</option>
                                </select>
                            </Field>
                            <Field name="Подтверждение перевода (обязательно для выплаты)">
                                <input className={inputClass} name="reference" required={withdrawalStatus === "paid"} />
                            </Field>
                            <Field name="Причина (обязательно при отклонении)">
                                <textarea className={inputClass} name="reason" required={withdrawalStatus === "rejected"} />
                            </Field>
                            <button className={buttonClass} disabled={busy}>
                                Сохранить состояние
                            </button>
                        </form>
                    )}
                </Section>
            )}
            {tab === "settings" && rates && (
                <>
                    <Section title="Ставки и ограничения">
                        <form
                            key={revision}
                            className="grid gap-3 sm:grid-cols-2"
                            onSubmit={(e) =>
                                void form(
                                    e,
                                    `${base}/settings`,
                                    (f) => ({
                                        levelsBps: [0, 1, 2, 3].map((i) => Math.round(num(f, `level${i}`) * 100)),
                                        partnerBps: Math.round(num(f, "partner") * 100),
                                        buyerBps: Math.round(num(f, "buyer") * 100),
                                        capBps: Math.round(num(f, "cap") * 100),
                                        holdDays: num(f, "hold"),
                                        minimumWithdrawalMinor: Math.round(num(f, "minimum") * 100),
                                        products: Object.fromEntries(
                                            value(f, "products")
                                                .split("\n")
                                                .filter(Boolean)
                                                .map((line) => {
                                                    const [id, factor] = line.split(":");
                                                    return [id.trim(), Math.round(Number(factor) * 100)];
                                                }),
                                        ),
                                    }),
                                    "PATCH",
                                )
                            }
                        >
                            {[
                                ...rates.levelsBps.map((n, i) => [`level${i}`, `Уровень ${i + 1}, %`, n / 100]),
                                ["partner", "Ближайший партнёр, %", rates.partnerBps / 100],
                                ["buyer", "Покупателю, %", rates.buyerBps / 100],
                                ["cap", "Лимит вознаграждений, %", rates.capBps / 100],
                                ["hold", "Удержание после доставки, дней", rates.holdDays],
                                ["minimum", "Минимальная выплата, ₽", rates.minimumWithdrawalMinor / 100],
                            ].map(([key, title, n]) => (
                                <Field key={key} name={String(title)}>
                                    <input
                                        className={inputClass}
                                        name={String(key)}
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        defaultValue={n}
                                        required
                                    />
                                </Field>
                            ))}
                            <Field name="Коэффициенты товаров: ID:процент, каждый с новой строки (0 — исключён; 100 — полная база)">
                                <textarea
                                    className={inputClass}
                                    name="products"
                                    defaultValue={Object.entries(rates.products)
                                        .map(([id, n]) => `${id}:${n / 100}`)
                                        .join("\n")}
                                />
                            </Field>
                            <button className={buttonClass} disabled={busy}>
                                Сохранить правила
                            </button>
                        </form>
                    </Section>
                    <Section title="Симулятор максимального полного распределения">
                        <p>Максимальные вознаграждения для четырёх уровней, партнёра и покупателя. Скидки и списанные бонусы учитываются отдельно. Без введённых расходов прибыль не рассчитывается.</p>
                        <form
                            className="flex flex-wrap gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const f = new FormData(e.currentTarget);
                                void mutate(`${base}/simulate`, { amount: value(f, "base"), discountAmount: value(f, "discount") || "0", bonusAmount: value(f, "spent") || "0", costAmount: value(f, "costs") })
                                    .then((r) => setSimulation(r || null))
                                    .catch(() => {});
                            }}
                        >
                            <Field name="Стоимость товаров до скидки и бонусов, ₽">
                                <input className={inputClass} name="base" type="number" min="0" step="0.01" required />
                            </Field>
                            {[["discount", "Скидка магазина, ₽"], ["spent", "Списание бонусов, ₽"], ["costs", "Себестоимость и все расходы на заказ, ₽"]].map(([name, title]) => (
                                <Field key={name} name={title}>
                                    <input className={inputClass} name={name} type="number" min="0" step="0.01" placeholder={name === "costs" ? "Не указаны" : "0"} />
                                </Field>
                            ))}
                            <button className={buttonClass} disabled={busy}>
                                Рассчитать
                            </button>
                        </form>
                        {simulation && (
                            <Rows
                                rows={Object.entries(simulation).map(([key, v]) => ({
                                    id: key,
                                    name:
                                        {
                                            grossMinor: "Стоимость товаров",
                                            discountMinor: "Скидка магазина",
                                            spentBonusMinor: "Списанные бонусы",
                                            basisMinor: "База начислений",
                                            totalIncentivesMinor: "Скидка + списание бонусов + вознаграждения",
                                            remainingBeforeCostsMinor: "Остаток до себестоимости и расходов",
                                            costMinor: "Указанные расходы",
                                            remainingAfterCostsMinor: "Остаток после указанных расходов",
                                            levelsMinor: "Четыре уровня",
                                            partnerMinor: "Партнёр",
                                            buyerMinor: "Резерв на новые бонусы покупателя",
                                            totalMinor: "Все вознаграждения",
                                            capMinor: "Лимит вознаграждений",
                                        }[key] || key,
                                    result: Array.isArray(v) ? v.map((n) => money(n)).join(" / ") : money(v),
                                }))}
                                columns={[
                                    ["name", "Показатель"],
                                    ["result", "Значение"],
                                ]}
                            />
                        )}
                    </Section>
                </>
            )}
            {tab === "ledger" && (
                <Section title="Корректировка с аудитом">
                    <form
                        className="grid gap-3 sm:grid-cols-2"
                        onSubmit={(e) =>
                            void form(e, `${base}/adjustments`, (f) => ({
                                userId: num(f, "userId"),
                                wallet: value(f, "wallet"),
                                amountMinor: Math.round(num(f, "amount") * 100),
                                reason: value(f, "reason"),
                            }))
                        }
                    >
                        <Field name="ID пользователя">
                            <input className={inputClass} name="userId" type="number" min="1" required />
                        </Field>
                        <Field name="Счёт">
                            <select className={inputClass} name="wallet">
                                <option value="shopping">Покупательские бонусы</option>
                                <option value="commission">Денежные комиссии</option>
                            </select>
                        </Field>
                        <Field name="Изменение, ₽ (минус — списание)">
                            <input className={inputClass} name="amount" type="number" step="0.01" required />
                        </Field>
                        <Field name="Обязательная причина">
                            <input className={inputClass} name="reason" required />
                        </Field>
                        <button className={buttonClass} disabled={busy}>
                            Записать корректировку
                        </button>
                    </form>
                </Section>
            )}
            {tab === "payments" && (
                <>
                    <Section title="Подключение оплаты">
                        <p>
                            {config?.configured ? "ЮKassa подключена" : "ЮKassa не настроена"}. Чеки:{" "}
                            {config?.receiptsEnabled ? "включены" : "выключены"}.
                        </p>
                        <button
                            className={buttonClass}
                            disabled={busy}
                            onClick={() => void mutate("/admin/api/payments/reconcile", {}).catch(() => {})}
                        >
                            Сверить зависшие платежи
                        </button>
                    </Section>
                    <Section title="Заказ: оплата, доставка и возврат">
                        <form
                            className="flex gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                setError("");
                                void request<typeof order>(`/admin/api/payments/orders/${encodeURIComponent(orderId)}`)
                                    .then((d) => {
                                        setOrder(d);
                                        const saved = sessionStorage.getItem(`biofarm_refund_${orderId}`);
                                        refund.current = saved ? JSON.parse(saved) : null;
                                    })
                                    .catch((e) => setError(String(e)));
                            }}
                        >
                            <Field name="Номер заказа">
                                <input
                                    className={inputClass}
                                    value={orderId}
                                    onChange={(e) => {
                                        setOrderId(e.target.value);
                                        setOrder(null);
                                        refund.current = null;
                                    }}
                                    required
                                />
                            </Field>
                            <button className={buttonClass}>Открыть</button>
                        </form>
                        {order && (
                            <>
                                <p>
                                    Оплата: {String(order.payment?.status || "—")} ·{" "}
                                    {money(order.payment?.amountMinor)}
                                </p>
                                <button
                                    className={buttonClass}
                                    disabled={busy}
                                    onClick={() =>
                                        void mutate(
                                            `${base}/orders/${encodeURIComponent(orderId)}/delivered`,
                                            {},
                                        ).catch(() => {})
                                    }
                                >
                                    Подтвердить доставку и начать удержание
                                </button>
                                <button type="button" className="rounded-lg border border-slate-300 px-4 py-2 text-sm" disabled={busy}
                                    onClick={() => void mutate(`/admin/api/payments/orders/${encodeURIComponent(orderId)}/receipt`, {}).then(() => request<typeof order>(`/admin/api/payments/orders/${encodeURIComponent(orderId)}`)).then(setOrder).catch(() => {})}>
                                    Отправить / проверить чек зачёта предоплаты
                                </button>
                                <p className="text-sm text-slate-500">Операция receipt — чек после доставки. queued: ожидает отправки; waiting_refund: ожидает возврата; retry_required: нужна повторная проверка; review_required или canceled: нужна сверка в ЮKassa. Ошибка чека не отменяет доставку.</p>
                                <form className="space-y-2 rounded-xl border border-slate-200 p-3" onSubmit={(event) => {
                                    event.preventDefault();
                                    const data = new FormData(event.currentTarget);
                                    void mutate(`/admin/api/payments/orders/${encodeURIComponent(orderId)}/receipt`, { providerReceiptId: String(data.get("providerReceiptId") || ""), reason: String(data.get("receiptReason") || "") })
                                        .then(() => request<typeof order>(`/admin/api/payments/orders/${encodeURIComponent(orderId)}`)).then(setOrder).catch(() => {});
                                }}>
                                    <p className="text-sm">Если чек уже зарегистрирован в ЮKassa, укажите его ID. Сервер проверит чек у провайдера; новый чек создан не будет.</p>
                                    <input name="providerReceiptId" required maxLength={64} placeholder="ID существующего чека ЮKassa" className={inputClass} />
                                    <input name="receiptReason" required maxLength={2000} placeholder="Причина ручной сверки" className={inputClass} />
                                    <button className={buttonClass} disabled={busy}>Проверить и привязать существующий чек</button>
                                </form>
                                <Rows
                                    rows={order.operations}
                                    columns={[
                                        ["created_at", "Дата"],
                                        ["kind", "Операция"],
                                        ["status", "Состояние"],
                                        ["amount_minor", "Сумма"],
                                    ]}
                                />
                                <form
                                    className="space-y-3"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const f = new FormData(e.currentTarget);
                                        const body = {
                                            items: order.items
                                                .map((i) => ({ itemId: Number(i.id), quantity: num(f, `item${i.id}`) }))
                                                .filter((i) => i.quantity > 0),
                                            refundDelivery: f.get("delivery") === "on",
                                        };
                                        if (!refund.current) {
                                            refund.current = { id: crypto.randomUUID(), body };
                                            sessionStorage.setItem(
                                                `biofarm_refund_${orderId}`,
                                                JSON.stringify(refund.current),
                                            );
                                        } else if (JSON.stringify(refund.current.body) !== JSON.stringify(body)) {
                                            setError(
                                                "Есть незавершённая попытка. Повторите тот же состав или начните новую операцию после проверки журнала.",
                                            );
                                            return;
                                        }
                                        void mutate(
                                            `/admin/api/payments/orders/${encodeURIComponent(orderId)}/refund`,
                                            { ...refund.current.body, requestId: refund.current.id },
                                        )
                                            .then(() => {
                                                setNotice(
                                                    "Запрос возврата принят. Проверьте состояние в журнале операций.",
                                                );
                                            })
                                            .catch(() => {});
                                    }}
                                >
                                    <h3 className="font-medium">Возврат товаров</h3>
                                    {order.items.map((i) => (
                                        <Field key={String(i.id)} name={`${i.product_name} (в заказе ${i.quantity})`}>
                                            <input
                                                className={inputClass}
                                                name={`item${i.id}`}
                                                type="number"
                                                min="0"
                                                max={Number(i.quantity)}
                                                defaultValue="0"
                                            />
                                        </Field>
                                    ))}
                                    <label>
                                        <input name="delivery" type="checkbox" /> Вернуть доставку
                                    </label>
                                    <div className="flex flex-wrap gap-3">
                                        <button className={buttonClass} disabled={busy}>
                                            Отправить / повторить тот же возврат
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        "Проверили, что предыдущая операция завершена? Создать отдельный новый возврат?",
                                                    )
                                                ) {
                                                    refund.current = null;
                                                    sessionStorage.removeItem(`biofarm_refund_${orderId}`);
                                                    setNotice("Новая операция возврата");
                                                }
                                            }}
                                        >
                                            Начать отдельный возврат
                                        </button>
                                    </div>
                                </form>
                            </>
                        )}
                    </Section>
                </>
            )}
        </div>
    );
}
