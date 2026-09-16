import { useEffect, useState, type FormEvent } from "react";
import { request } from "../../../site/api";
import { readCart } from "../../../site/cart";
import {
    Section,
    Field,
    LinkQR,
    Rows,
    Pager,
    money,
    inputClass,
    buttonClass,
    type Dashboard,
    type Listing,
} from "../../../program/shared";
export function ReferralPanel({ withdrawalsEnabled }: { withdrawalsEnabled: boolean }) {
    const [data, setData] = useState<Dashboard | null>(null);
    const [tab, setTab] = useState("team");
    const [page, setPage] = useState(1);
    const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [revision, setRevision] = useState(0);
    const [offerUrl, setOfferUrl] = useState("");
    useEffect(() => {
        let live = true;
        void request<Dashboard>("/v1/program")
            .then((d) => {
                if (live) setData(d);
            })
            .catch((e) => setError(String(e)));
        return () => {
            live = false;
        };
    }, [revision]);
    useEffect(() => {
        let live = true;
        void request<Listing>(`/v1/program/${tab}?page=${page}`)
            .then((d) => {
                if (live) setList(d);
            })
            .catch((e) => setError(String(e)));
        return () => {
            live = false;
        };
    }, [tab, page, revision]);
    async function submit(
        event: FormEvent<HTMLFormElement>,
        path: string,
        body: (f: FormData) => Record<string, unknown>,
    ) {
        event.preventDefault();
        const form = event.currentTarget;
        setBusy(true);
        setError("");
        try {
            const result = await request<{ url?: string }>(`/v1/program/${path}`, {
                method: "POST",
                body: body(new FormData(form)),
            });
            if (result?.url) setOfferUrl(result.url);
            form.reset();
            setRevision((n) => n + 1);
        } catch (e) {
            setError(String(e));
        } finally {
            setBusy(false);
        }
    }
    if (!data) return <p role="status">{error || "Загрузка программы…"}</p>;
    const columns: Record<string, [string, string][]> = {
        team: [
            ["id", "ID"],
            ["first_name", "Имя"],
            ["last_name", "Фамилия"],
            ["depth", "Уровень"],
            ["is_partner", "Партнёр"],
            ["referred_by_user_id", "Пригласивший ID"],
        ],
        ledger: [
            ["created_at", "Дата"],
            ["kind", "Операция"],
            ["wallet", "Счёт"],
            ["state", "Состояние"],
            ["order_id", "Заказ"],
            ["amount_minor", "Сумма"],
            ["details", "Детали"],
        ],
        sales: [
            ["id", "Заказ"],
            ["status", "Состояние"],
            ["delivered_at", "Доставлен"],
            ["goods_minor", "Оплачено за товары"],
            ["earned_minor", "Мои комиссии"],
        ],
        withdrawals: [
            ["id", "Заявка"],
            ["created_at", "Дата"],
            ["amount_minor", "Сумма"],
            ["status", "Состояние"],
            ["reason", "Причина"],
            ["reference", "Подтверждение"],
        ],
        offers: [
            ["title", "Название"],
            ["expires_at", "Срок"],
            ["visits", "Просмотры"],
            ["orders_count", "Заказы"],
            ["paid_count", "Оплачено"],
        ],
    };
    return (
        <div className="space-y-6">
            {error && (
                <p role="alert" className="rounded-lg bg-red-50 p-3 text-red-700">
                    {error}
                </p>
            )}
            <Section title={data.identity.isPartner ? "Партнёрская программа" : "Реферальная программа"}>
                <p>
                    Участие бесплатно. Начисления — после подтверждённой оплаты; доступность — после доставки и
                    удержания {data.rates.holdDays} дней.
                </p>
                <p>
                    Уровни: {data.rates.levelsBps.map((n) => `${n / 100}%`).join(" / ")}. Ближайшему партнёру:{" "}
                    {data.rates.partnerBps / 100}%. Покупателю: {data.rates.buyerBps / 100}%.
                </p>
                <LinkQR url={`${window.location.origin}/?ref=${encodeURIComponent(data.identity.referralCode)}`} />
            </Section>
            <div className="grid gap-4 xl:grid-cols-2">
                {(["shopping", "commission"] as const).map((wallet) => (
                    <Section key={wallet} title={wallet === "shopping" ? "Покупательские бонусы" : "Денежные комиссии"}>
                        {(
                            [
                                ["availableMinor", "Доступно"],
                                ["pendingMinor", "На удержании"],
                                ["reservedMinor", "Зарезервировано"],
                                ["debtMinor", "Долг"],
                            ] as const
                        ).map(([key, title]) => (
                            <div key={key} className="flex justify-between gap-3">
                                <span>{title}</span>
                                <strong>{money(data.balances[wallet][key])}</strong>
                            </div>
                        ))}
                    </Section>
                ))}
            </div>
            {withdrawalsEnabled && (
                <Section title="Заявка на ручную выплату">
                    <p className="text-sm">
                        Минимум {money(data.rates.minimumWithdrawalMinor)}. Одобрение заявки не означает перевод.
                    </p>
                    <form
                        className="grid gap-3 sm:grid-cols-2"
                        onSubmit={(e) =>
                            void submit(e, "withdrawals", (f) => ({
                                amount: String(f.get("amount")),
                                details: {
                                    bank: f.get("bank"),
                                    recipient: f.get("recipient"),
                                    account: f.get("account"),
                                },
                            }))
                        }
                    >
                        <Field name="Сумма, ₽">
                            <input
                                className={inputClass}
                                name="amount"
                                type="number"
                                min={Math.max(0.01, data.rates.minimumWithdrawalMinor / 100)}
                                max={data.balances.commission.availableMinor / 100}
                                step="0.01"
                                required
                            />
                        </Field>
                        {[
                            ["bank", "Банк"],
                            ["recipient", "Получатель"],
                            ["account", "Счёт получателя"],
                        ].map(([name, title]) => (
                            <Field key={name} name={title}>
                                <input className={inputClass} name={name} required />
                            </Field>
                        ))}
                        <button className={buttonClass} disabled={busy || data.balances.commission.availableMinor <= 0}>
                            Отправить заявку
                        </button>
                    </form>
                </Section>
            )}
            {data.identity.isPartner && (
                <>
                    <Section title="Создать предложение из текущей корзины">
                        <p>
                            Добавьте товары в{" "}
                            <a className="underline" href="/catalog">
                                каталоге
                            </a>
                            . Будут использованы товары и количества текущей корзины; цены проверяются при открытии
                            предложения.
                        </p>
                        <ul>
                            {readCart().map((i) => (
                                <li key={i.product.id}>
                                    {i.product.name} × {i.quantity}
                                </li>
                            ))}
                        </ul>
                        <form
                            className="grid gap-3 sm:grid-cols-2"
                            onSubmit={(e) =>
                                void submit(e, "offers", (f) => ({
                                    title: f.get("title"),
                                    expiresAt: f.get("expiresAt")
                                        ? new Date(String(f.get("expiresAt"))).toISOString()
                                        : undefined,
                                    items: readCart().map((i) => ({ productId: i.product.id, quantity: i.quantity })),
                                }))
                            }
                        >
                            <Field name="Название">
                                <input className={inputClass} name="title" required />
                            </Field>
                            <Field name="Действует до">
                                <input className={inputClass} type="datetime-local" name="expiresAt" />
                            </Field>
                            <button disabled={busy || !readCart().length} className={buttonClass}>
                                Создать предложение
                            </button>
                        </form>
                        {offerUrl && <LinkQR url={offerUrl} />}
                    </Section>
                </>
            )}
            <Section title="История и команда">
                <div className="flex flex-wrap gap-2">
                    {[
                        ["team", "Команда"],
                        ["sales", "Продажи"],
                        ["ledger", "Операции"],
                        ...(withdrawalsEnabled ? [["withdrawals", "Выплаты"]] : []),
                        ...(data.identity.isPartner ? [["offers", "Предложения"]] : []),
                    ].map(([key, title]) => (
                        <button
                            type="button"
                            aria-pressed={tab === key}
                            className={tab === key ? buttonClass : "rounded-lg border p-2 text-sm"}
                            key={key}
                            onClick={() => {
                                setTab(key);
                                setPage(1);
                            }}
                        >
                            {title}
                        </button>
                    ))}
                </div>
                <Rows
                    rows={list.items}
                    columns={columns[tab]}
                    actions={
                        tab === "offers"
                            ? (row) => (
                                  <>
                                      <LinkQR url={String(row.url)} />
                                      <button
                                          className={buttonClass}
                                          disabled={!row.is_active || busy}
                                          onClick={() => {
                                              setBusy(true);
                                              void request(`/v1/program/offers/${row.id}`, {
                                                  method: "PATCH",
                                                  body: { isActive: false },
                                              })
                                                  .then(() => setRevision((n) => n + 1))
                                                  .catch((e) => setError(String(e)))
                                                  .finally(() => setBusy(false));
                                          }}
                                      >
                                          Отключить
                                      </button>
                                  </>
                              )
                            : undefined
                    }
                />
                <Pager page={page} setPage={setPage} hasMore={list.items.length === list.limit} />
            </Section>
        </div>
    );
}
