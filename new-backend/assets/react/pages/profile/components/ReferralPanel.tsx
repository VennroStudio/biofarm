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
import { TabButton, TabList, TabPanel } from "./ProfileTabs";
import { Users, ShoppingBasket, Wallet, ReceiptText, Link, ListOrdered } from "lucide-react";

export function ReferralPanel({ withdrawalsEnabled }: { withdrawalsEnabled: boolean }) {
    const [data, setData] = useState<Dashboard | null>(null);
    const [tab, setTab] = useState("team");
    const [sort, setSort] = useState("depth");
    const [direction, setDirection] = useState<"asc" | "desc">("asc");
    const [loadedKey, setLoadedKey] = useState("");
    const [notice, setNotice] = useState("");
    const [page, setPage] = useState(1);
    const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [revision, setRevision] = useState(0);
    const [offerUrl, setOfferUrl] = useState("");
    const listKey = `${tab}:${page}:${sort}:${direction}:${revision}`;
    const loadingList = loadedKey !== listKey;
    const cart = readCart();
    useEffect(() => {
        let live = true;
        void request<Dashboard>("/v1/program")
            .then((d) => {
                if (live) setData(d);
            })
            .catch((e) => { if (live) setError(String(e)); });
        return () => {
            live = false;
        };
    }, [revision]);
    useEffect(() => {
        if (tab === "invite") return;
        let live = true;
        void request<Listing>(`/v1/program/${tab}?page=${page}&sort=${sort}&direction=${direction}${tab === "ledger" ? "&wallet=commission" : ""}`)
            .then((d) => {
                if (live) { setList(d); setError(""); }
            })
            .catch((e) => { if (live) { setError(String(e)); setList({ items: [], page, limit: 25 }); } })
            .finally(() => { if (live) setLoadedKey(listKey); });
        return () => {
            live = false;
        };
    }, [tab, page, revision, sort, direction, listKey]);
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
            setNotice(path === "withdrawals" ? "Заявка отправлена. Её состояние появится в истории выплат." : "Корзина готова. Отправьте покупателю ссылку или QR-код.");
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
        team: [["name", "Участник"], ["depth", "Уровень"], ["parent_name", "Кто пригласил"]],
        ledger: [
            ["created_at", "Дата"],
            ["kind", "Операция"],
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
    const tabs = [
        { key: "team", title: "Моя команда", icon: Users },
        { key: "sales", title: "Продажи", icon: ReceiptText },
        { key: "ledger", title: "Начисления", icon: ListOrdered },
        { key: "invite", title: "Пригласить", icon: Link },
        ...(data.identity.isPartner ? [{ key: "offers", title: "Корзина", icon: ShoppingBasket }] : []),
        ...(withdrawalsEnabled ? [{ key: "withdrawals", title: "Выплаты", icon: Wallet }] : []),
    ];
    const title = tabs.find((item) => item.key === tab)?.title || "Моя команда";
    return (
        <div className="grid items-start gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8">
            <TabList label="Разделы программы">
                {tabs.map(({ key, title: tabTitle, icon: Icon }) => (
                    <TabButton key={key} id={`program-tab-${key}`} controls={`program-panel-${key}`}
                        active={tab === key} onClick={() => { setTab(key); setPage(1); setError(""); setNotice(""); }}>
                        <Icon className="h-4 w-4 shrink-0" /><span>{tabTitle}</span>
                    </TabButton>
                ))}
            </TabList>
            <div className="min-w-0">
                {tabs.filter((item) => item.key !== tab).map((item) => (
                    <TabPanel key={item.key} active={false} id={`program-panel-${item.key}`} labelledBy={`program-tab-${item.key}`}>{null}</TabPanel>
                ))}
                <TabPanel active id={`program-panel-${tab}`} labelledBy={`program-tab-${tab}`}>
                    <div className="space-y-6">
                        {error && <p role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">{error}</p>}
                        {notice && <p role="status" className="rounded-xl bg-secondary p-4 text-primary">{notice}</p>}
                        {tab === "invite" && (
                            <Section title="Пригласить в команду">
                                <p className="text-sm text-muted-foreground">Отправьте свою ссылку. После регистрации приглашённый появится в команде. Покупки без регистрации учитываются в продажах.</p>
                                <LinkQR url={`${window.location.origin}/?ref=${encodeURIComponent(data.identity.referralCode)}`} />
                                <details className="border-t border-border pt-4 text-sm">
                                    <summary className="cursor-pointer text-primary">Условия начислений</summary>
                                    <div className="mt-3 space-y-2 text-muted-foreground">
                                        <p>Начисления — после подтверждённой оплаты. Доступны после доставки и удержания {data.rates.holdDays} дней.</p>
                                        <p>Четыре уровня: {data.rates.levelsBps.map((n) => `${n / 100}%`).join(" / ")}. Ближайшему партнёру: {data.rates.partnerBps / 100}%.</p>
                                    </div>
                                </details>
                            </Section>
                        )}
                        {(tab === "withdrawals" || (tab === "ledger" && !withdrawalsEnabled)) && (
                            <Section title="Деньги на выплату">
                                <div className="grid grid-cols-2 gap-5 xl:grid-cols-4">
                                    {([["availableMinor", "Доступно к выводу"], ["pendingMinor", "На удержании"], ["reservedMinor", "В заявках"], ["debtMinor", "Долг"]] as const).map(([key, caption]) => (
                                        <div key={key}><p className="text-sm text-muted-foreground">{caption}</p><strong className="mt-2 block break-words text-2xl text-primary">{money(data.balances.commission[key])}</strong></div>
                                    ))}
                                </div>
                                <p className="text-sm text-muted-foreground">Выплаты выполняются вручную. Покупательские бонусы учитываются отдельно в личном кабинете.</p>
                            </Section>
                        )}
                        {tab === "withdrawals" && withdrawalsEnabled && (
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
                                    <button className={buttonClass} disabled={busy || data.balances.commission.availableMinor < Math.max(1, data.rates.minimumWithdrawalMinor)}>
                                        Отправить заявку
                                    </button>
                                </form>
                            </Section>
                        )}
                        {tab === "offers" && data.identity.isPartner && (
                            <>
                                <Section title="Корзина для покупателя">
                                    <p>
                                        Добавьте товары в{" "}
                                        <a className="underline" href="/catalog">
                                            каталоге
                                        </a>
                                        . Будут использованы товары и количества текущей корзины; цены проверяются при открытии
                                        предложения.
                                    </p>
                                    {!cart.length && <p className="rounded-xl bg-secondary/50 p-4 text-sm text-muted-foreground">Корзина пуста. Сначала выберите товары в каталоге.</p>}
                                    <ul className="space-y-2">
                                        {cart.map((i) => (
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
                                        <button disabled={busy || !cart.length} className={buttonClass}>
                                            Создать ссылку и QR-код
                                        </button>
                                    </form>
                                    {offerUrl && <LinkQR url={offerUrl} />}
                                </Section>
                            </>
                        )}

                        {tab !== "invite" && (
                            <Section title={tab === "offers" ? "Отправленные корзины" : tab === "withdrawals" ? "История выплат" : title}>
                                {tab === "team" && <p className="text-sm text-muted-foreground">Уровень 1 — приглашённые вами лично. Нажмите на заголовок столбца для сортировки всей команды. Участник, ставший партнёром, уходит вместе со своей веткой.</p>}
                                {tab === "sales" && <p className="text-sm text-muted-foreground">Оплаченные заказы, по которым вам начислена комиссия. Сумма комиссии учитывает возвраты.</p>}
                                {tab === "ledger" && <p className="text-sm text-muted-foreground">История денежных комиссий. Состояние показывает, доступно ли начисление.</p>}
                                <Rows
                                    loading={loadingList}
                                    rows={list.items}
                                    columns={columns[tab]}
                                    sorting={tab === "team" ? {
                                        key: sort, direction,
                                        onChange: (key) => {
                                            setDirection(key === sort && direction === "asc" ? "desc" : "asc");
                                            setSort(key);
                                            setPage(1);
                                        },
                                    } : undefined}
                                    actions={
                                        tab === "offers"
                                            ? (row) => (
                                                  <>
                                                      <details>
                                                          <summary className="cursor-pointer whitespace-nowrap text-primary">Ссылка и QR-код</summary>
                                                          <div className="mt-3 min-w-48"><LinkQR url={String(row.url)} /></div>
                                                      </details>
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
                                {!loadingList && <Pager page={page} setPage={setPage} hasMore={list.items.length === list.limit} />}

                            </Section>
                        )}
                    </div>
                </TabPanel>
            </div>
        </div>
    );
}
