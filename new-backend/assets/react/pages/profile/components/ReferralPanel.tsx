import { useEffect, useState, type FormEvent } from "react";
import { request } from "../../../site/api";
import {
    Section,
    Field,
    Rows,
    Pager,
    money,
    inputClass,
    buttonClass,
    type Dashboard,
    type Listing,
} from "../../../program/shared";
import { TabButton, TabList, TabPanel } from "./ProfileTabs";
import { Users, ShoppingBasket, Wallet, ReceiptText, UserPlus, ListOrdered, QrCode, Ban, Info } from "lucide-react";

import { OfferDetailsDialog } from "./OfferDetailsDialog";
import { OfferLinkDialog } from "./OfferLinkDialog";

const offerActionClass = "inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border text-primary transition-colors hover:bg-secondary focus-visible:outline-primary focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-35";

export function ReferralPanel({ withdrawalsEnabled }: { withdrawalsEnabled: boolean }) {
    const [data, setData] = useState<Dashboard | null>(null);
    const [tab, setTab] = useState("");
    const [sort, setSort] = useState("depth");
    const [direction, setDirection] = useState<"asc" | "desc">("asc");
    const [loadedKey, setLoadedKey] = useState("");
    const [notice, setNotice] = useState("");
    const [page, setPage] = useState(1);
    const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [revision, setRevision] = useState(0);
    const [offerId, setOfferId] = useState("");
    const [offerUrl, setOfferUrl] = useState("");
    const [inviteOpen, setInviteOpen] = useState<"team" | "referrals" | null>(null);
    const [teamInviteCode, setTeamInviteCode] = useState("");
    const listKey = `${tab}:${page}:${sort}:${direction}:${revision}`;
    const loadingList = loadedKey !== listKey;
    useEffect(() => {
        let live = true;
        void request<Dashboard>("/v1/program")
            .then((d) => {
                if (live) { setData(d); setTab((current) => current || (d.identity.isPartner ? "team" : d.identity.isTeamMember ? "referrals" : "ledger")); }
            })
            .catch((e) => { if (live) setError(String(e)); });
        return () => {
            live = false;
        };
    }, [revision]);
    useEffect(() => {
        let live = true;
        if (!tab || !data) return;
        void request<Listing>(`/v1/program/${tab}?page=${page}&sort=${sort}&direction=${direction}${tab === "ledger" ? "&wallet=commission" : ""}`)
            .then((d) => {
                if (live) { setList(d); setError(""); }
            })
            .catch((e) => { if (live) { setError(String(e)); setList({ items: [], page, limit: 25 }); } })
            .finally(() => { if (live) setLoadedKey(listKey); });
        return () => {
            live = false;
        };
    }, [tab, page, revision, sort, direction, listKey, data]);
    async function submitWithdrawal(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        const fields = new FormData(form);
        setBusy(true);
        setError("");
        try {
            await request("/v1/program/withdrawals", {
                method: "POST",
                body: {
                    amount: String(fields.get("amount")),
                    details: {
                        bank: fields.get("bank"),
                        recipient: fields.get("recipient"),
                        account: fields.get("account"),
                    },
                },
            });
            setNotice("Заявка отправлена. Её состояние появится в истории выплат.");
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
        team: [["name", "Участник"], ["created_at", "Регистрация"]],
        referrals: [["name", "Покупатель"], ["created_at", "Регистрация"]],
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
        ...(data.identity.isPartner ? [{ key: "team", title: "Моя команда", icon: Users }] : []),
        ...(data.identity.canEarnCommission ? [{ key: "referrals", title: "Мои рефералы", icon: Users }] : []),
        ...(data.identity.isPartner ? [{ key: "sales", title: "Продажи", icon: ReceiptText }] : []),
        { key: "ledger", title: "Начисления", icon: ListOrdered },
        ...(data.identity.isPartner ? [{ key: "offers", title: "Корзина", icon: ShoppingBasket }] : []),
        ...(withdrawalsEnabled ? [{ key: "withdrawals", title: "Выплаты", icon: Wallet }] : []),
    ];
    const title = tabs.find((item) => item.key === tab)?.title || "Моя команда";
    return (
        <div className="grid items-start gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8">
            {offerId && <OfferDetailsDialog key={offerId} offerId={offerId} onClose={() => setOfferId("")} />}
            {offerUrl && <OfferLinkDialog key={offerUrl} url={offerUrl} onClose={() => setOfferUrl("")} />}
            {inviteOpen && (
                <OfferLinkDialog url={inviteOpen === "team" ? `${window.location.origin}/login?team=${encodeURIComponent(teamInviteCode)}` : `${window.location.origin}/?ref=${encodeURIComponent(data.identity.referralCode)}`}
                    title={inviteOpen === "team" ? "Пригласить в команду" : "Ссылка для покупателей"}
                    description={inviteOpen === "team" ? "Приглашённый войдёт или зарегистрируется и подтвердит вступление в вашу команду." : "Покупки по этой ссылке дают вам комиссию. Регистрация покупателя не делает его участником команды."}
                    onClose={() => setInviteOpen(null)}>
                    <details className="border-t border-border pt-4 text-sm">
                        <summary className="cursor-pointer text-primary">Условия начислений</summary>
                        <div className="mt-3 space-y-2 text-muted-foreground">
                            <p>Начисления — после подтверждённой оплаты. Доступны после доставки и удержания {data.rates.holdDays} дней.</p>
                            <p>Ваша комиссия за покупателя — {(data.identity.isPartner ? data.rates.partnerDirectBps : data.rates.memberDirectBps) / 100}%. {data.identity.isPartner ? `С личных покупок участников команды — ${data.rates.partnerMemberBps / 100}%; с покупок их рефералов — ${data.rates.partnerTeamBps / 100}%.` : `Партнёру вашей команды отдельно начисляется ${data.rates.partnerTeamBps / 100}%.`}</p>
                        </div>
                    </details>
                </OfferLinkDialog>
            )}
            <TabList label="Разделы программы">
                {tabs.map(({ key, title: tabTitle, icon: Icon }) => (
                    <TabButton key={key} id={`program-tab-${key}`} controls={`program-panel-${key}`}
                        active={tab === key} onClick={() => { setTab(key); setSort("depth"); setDirection("asc"); setPage(1); setError(""); setNotice(""); }}>
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
                                    onSubmit={(event) => void submitWithdrawal(event)}
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
                            <Section title={tab === "offers" ? "История корзин" : tab === "withdrawals" ? "История выплат" : title}>
                                {(tab === "team" || tab === "referrals") && (
                                    <div className="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                                        <p className="text-sm text-muted-foreground">{tab === "team" ? "Только пользователи, вступившие по специальному приглашению." : "Покупатели, зарегистрировавшиеся по вашей ссылке. Гостевые покупки учитываются в начислениях."}</p>
                                        <button type="button" className={`${buttonClass} inline-flex shrink-0 items-center gap-2`} disabled={busy} onClick={() => {
                                            if (tab === "referrals") { setInviteOpen("referrals"); return; }
                                            setBusy(true);
                                            void request<{ code: string }>("/v1/program/team-invitation")
                                                .then((r) => { setTeamInviteCode(r.code); setInviteOpen("team"); })
                                                .catch((e) => setError(String(e))).finally(() => setBusy(false));
                                        }}>
                                            <UserPlus className="h-4 w-4" aria-hidden="true" />{tab === "team" ? "Пригласить в команду" : "Моя ссылка и QR-код"}
                                        </button>
                                    </div>
                                )}
                                {tab === "team" && <p className="text-sm text-muted-foreground">Участник, ставший партнёром, выходит из команды и сохраняет своих покупателей.</p>}
                                {tab === "sales" && <p className="text-sm text-muted-foreground">Оплаченные заказы, по которым вам начислена комиссия. Сумма комиссии учитывает возвраты.</p>}
                                {tab === "ledger" && <p className="text-sm text-muted-foreground">История денежных комиссий. Состояние показывает, доступно ли начисление.</p>}
                                <Rows
                                    loading={loadingList}
                                    rows={list.items}
                                    columns={columns[tab]}
                                    sorting={tab === "team" || tab === "referrals" ? {
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
                                                  <div className="flex flex-nowrap items-center gap-2">
                                                      <button type="button" className={offerActionClass}
                                                          aria-label={`Информация о корзине: ${row.title}`} title="Информация о корзине"
                                                          onClick={() => setOfferId(String(row.id))}>
                                                          <Info className="h-5 w-5" aria-hidden="true" />
                                                      </button>
                                                      <button type="button" className={offerActionClass}
                                                          aria-label={`Ссылка и QR-код: ${row.title}`} title="Ссылка и QR-код"
                                                          disabled={!Number(row.is_active)} onClick={() => setOfferUrl(String(row.url))}>
                                                          <QrCode className="h-5 w-5" aria-hidden="true" />
                                                      </button>
                                                      <button
                                                          type="button" className={`${offerActionClass} hover:border-red-200 hover:bg-red-50 hover:text-red-600`}
                                                          aria-label={`Отключить корзину: ${row.title}`} title={Number(row.is_active) ? "Отключить корзину" : "Корзина отключена"}
                                                          disabled={!Number(row.is_active) || busy}
                                                          onClick={() => {
                                                              setBusy(true);
                                                              void request(`/v1/program/offers/${row.id}`, {
                                                                  method: "PATCH",
                                                                  body: { isActive: false },
                                                              })
                                                                  .then(() => { setNotice("Корзина отключена. Ссылка больше недоступна."); setRevision((n) => n + 1); })
                                                                  .catch((e) => setError(String(e)))
                                                                  .finally(() => setBusy(false));
                                                          }}
                                                      >
                                                          <Ban className="h-5 w-5" aria-hidden="true" />
                                                      </button>
                                                  </div>
                                              )
                                            : undefined
                                    }
                                />
                                {!loadingList && <Pager page={page} setPage={setPage} hasMore={list.items.length === list.limit} />}

                            </Section>
                    </div>
                </TabPanel>
            </div>
        </div>
    );
}
