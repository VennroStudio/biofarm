import { useEffect, useState, type FormEvent } from "react";
import { BookOpen, History, SlidersHorizontal, Wallet } from "lucide-react";
import { request } from "../api/client";
import {
    Section,
    Field,
    Rows,
    Pager,
    inputClass,
    buttonClass,
    type Rates,
    type Listing,
    type Row,
} from "../../program/shared";
import { ProgramSettings } from "./ProgramSettings";
const base = "/admin/api/program";
const value = (f: FormData, key: string) => String(f.get(key) || "");
const num = (f: FormData, key: string) => Number(f.get(key));
const defaultLedgerFilters = { sort: "created_at:desc", dateFrom: "", dateTo: "" };
const sections = [
    { key: "ledger", title: "Журнал", icon: BookOpen },
    { key: "audit", title: "Аудит", icon: History },
    { key: "withdrawals", title: "Выплаты", icon: Wallet },
    { key: "settings", title: "Настройки", icon: SlidersHorizontal },
];
export function AdminProgram() {
    const [withdrawalStatus, setWithdrawalStatus] = useState("approved");
    const [tab, setTab] = useState("ledger");
    const [page, setPage] = useState(1);
    const [ledgerFilters, setLedgerFilters] = useState(defaultLedgerFilters);
    const [ledgerDraft, setLedgerDraft] = useState(defaultLedgerFilters);
    const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
    const [rates, setRates] = useState<Rates | null>(null);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [revision, setRevision] = useState(0);
    const [busy, setBusy] = useState(false);
    const [selected, setSelected] = useState<Row | null>(null);
    useEffect(() => {
        void request<Rates>(`${base}/settings`)
            .then(setRates)
            .catch((e) => setError(String(e)));
    }, [revision]);
    useEffect(() => {
        if (tab === "settings") return;
        let live = true;
        const query = new URLSearchParams({ page: String(page) });
        if (tab === "ledger") {
            const [sort, direction] = ledgerFilters.sort.split(":");
            query.set("wallet", "commission");
            query.set("sort", sort);
            query.set("direction", direction);
            if (ledgerFilters.dateFrom) query.set("dateFrom", ledgerFilters.dateFrom);
            if (ledgerFilters.dateTo) query.set("dateTo", ledgerFilters.dateTo);
        }
        void request<Listing>(`${base}/${tab}?${query}`)
            .then((d) => {
                if (live) setList(d);
            })
            .catch((e) => { if (live) setError(String(e)); });
        return () => {
            live = false;
        };
    }, [tab, page, revision, ledgerFilters]);
    function applyLedgerFilters(filters: typeof defaultLedgerFilters) {
        setLedgerFilters({ ...filters });
        setPage(1);
        setList({ items: [], page: 1, limit: 25 });
        setError("");
    }
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
        ledger: [
            ["created_at", "Дата"],
            ["user_name", "Пользователь"],
            ["participant_type", "Статус пользователя"],
            ["kind", "Операция"],
            ["state", "Состояние"],
            ["order_id", "Заказ"],
            ["amount_minor", "Сумма"],
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
            <nav aria-label="Разделы партнёрской программы" className="flex flex-wrap gap-2 rounded-2xl border border-[#dfece9] bg-[#f4faf8] p-2">
                {sections.map(({ key, title, icon: Icon }) => (
                    <button
                        type="button"
                        aria-pressed={tab === key}
                        className={`inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-colors focus-visible:outline-offset-2 focus-visible:outline-[#2e8175] ${tab === key ? "bg-[#2e8175] text-white shadow-sm" : "text-[#526d78] hover:bg-white hover:text-[#18574f]"}`}
                        key={key}
                        onClick={() => {
                            setTab(key);
                            setPage(1);
                            setSelected(null);
                            setList({ items: [], page: 1, limit: 25 });
                        }}
                    >
                        <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />
                        {title}
                    </button>
                ))}
            </nav>
            {error && (
                <p role="alert" className="rounded bg-red-50 p-3 text-red-700">
                    {error}
                </p>
            )}
            {notice && <p role="status">{notice}</p>}
            {tab !== "settings" && (
                <Section title={tab === "ledger" ? "Журнал комиссий" : "Записи"}>
                    {tab === "ledger" && (
                        <ol className="list-decimal space-y-2 rounded-xl border border-border bg-secondary/40 py-4 pl-10 pr-5 text-sm leading-relaxed">
                            <li><strong>Ожидает</strong> — оплата подтверждена, но начисление ещё удерживается до доставки и окончания установленного срока.</li>
                            <li><strong>Доступно</strong> — операция учитывается в доступном балансе. <strong>Это не означает, что деньги уже выплачены.</strong></li>
                            <li><strong>Зарезервировано</strong> — сумма временно заблокирована, например под заявку на выплату.</li>
                            <li><strong>Отменено</strong> — операция не учитывается в балансе.</li>
                        </ol>
                    )}
                    {tab === "ledger" && (
                        <form className="flex flex-wrap items-end gap-4" onSubmit={(event) => {
                            event.preventDefault();
                            if (ledgerDraft.dateFrom && ledgerDraft.dateTo && ledgerDraft.dateFrom > ledgerDraft.dateTo) {
                                setError("Начало периода не может быть позже окончания");
                                return;
                            }
                            applyLedgerFilters(ledgerDraft);
                        }}>
                            <Field name="Сортировка">
                                <select className={inputClass} value={ledgerDraft.sort} onChange={(event) => setLedgerDraft({ ...ledgerDraft, sort: event.target.value })}>
                                    <option value="created_at:desc">Сначала новые</option>
                                    <option value="created_at:asc">Сначала старые</option>
                                    <option value="user_name:asc">Пользователь: А—Я</option>
                                    <option value="user_name:desc">Пользователь: Я—А</option>
                                </select>
                            </Field>
                            <Field name="Дата с">
                                <input className={inputClass} type="date" value={ledgerDraft.dateFrom} max={ledgerDraft.dateTo || undefined} onChange={(event) => setLedgerDraft({ ...ledgerDraft, dateFrom: event.target.value })} />
                            </Field>
                            <Field name="Дата по (включительно)">
                                <input className={inputClass} type="date" value={ledgerDraft.dateTo} min={ledgerDraft.dateFrom || undefined} onChange={(event) => setLedgerDraft({ ...ledgerDraft, dateTo: event.target.value })} />
                            </Field>
                            <button className={buttonClass} type="submit">Применить</button>
                            <button className="rounded-lg border border-border px-4 py-2 text-sm" type="button" onClick={() => {
                                setLedgerDraft(defaultLedgerFilters);
                                applyLedgerFilters(defaultLedgerFilters);
                            }}>Сбросить</button>
                        </form>
                    )}
                    <Rows
                        rows={list.items}
                        columns={columns[tab]}
                        actions={
                            tab === "withdrawals"
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
            {selected && tab === "withdrawals" && (
                <Section title={`Управление: ${selected.name || selected.id}`}>
                    <button className="underline" onClick={() => setSelected(null)}>
                        Закрыть
                    </button>
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
                <ProgramSettings key={JSON.stringify(rates)} rates={rates} busy={busy}
                    onSave={(body) => mutate(`${base}/settings`, body, "PATCH")} />
            )}
            {tab === "ledger" && (
                <Section title="Корректировка с аудитом">
                    <form
                        className="grid gap-3 sm:grid-cols-2"
                        onSubmit={(e) =>
                            void form(e, `${base}/adjustments`, (f) => ({
                                userId: num(f, "userId"),
                                wallet: "commission",
                                amountMinor: Math.round(num(f, "amount") * 100),
                                reason: value(f, "reason"),
                            }))
                        }
                    >
                        <Field name="ID пользователя">
                            <input className={inputClass} name="userId" type="number" min="1" required />
                        </Field>
                        <p className="self-center text-sm">Счёт: денежные комиссии</p>
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

        </div>
    );
}
