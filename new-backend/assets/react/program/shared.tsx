import { useEffect, useState, type ReactNode } from "react";
import QRCode from "qrcode";
export type Row = Record<string, unknown>;
export type Listing = { items: Row[]; page: number; limit: number };
export type Rates = {
    levelsBps: number[];
    partnerBps: number;
    buyerBps: number;
    capBps: number;
    holdDays: number;
    minimumWithdrawalMinor: number;
    maxPromoPercent: number;
    products: Record<string, number>;
};
export type Dashboard = {
    identity: {
        userId: number;
        isPartner: boolean;
        isReferral: boolean;
        parentId: number | null;
        referralCode: string;
    };
    balances: Record<
        "shopping" | "commission",
        { availableMinor: number; pendingMinor: number; reservedMinor: number; debtMinor: number }
    >;
    rates: Rates;
};
export const money = (value: unknown) =>
    new Intl.NumberFormat("ru-RU", { style: "currency", currency: "RUB", minimumFractionDigits: 2 }).format(
        Number(value || 0) / 100,
    );
export const label = (value: unknown) =>
    ({
        pending: "Ожидает",
        approved: "Одобрено, перевод не выполнен",
        paid: "Выплачено",
        rejected: "Отклонено",
        available: "Доступно",
        reserved: "Зарезервировано",
        refund_pending: "Возврат ожидается",
        completed: "Оплачено",
        refunded: "Возвращено",
        order_spending: "Оплата бонусами",
        refund_restore: "Возврат бонусов",
        payout: "Выплата",
        void: "Отменено",
        cancelled: "Отменено",
        commission: "Комиссия",
        shopping: "Бонусы",
        buyer: "Покупательские бонусы",
        referral: "Реферальное начисление",
        level_1: "Реферальное начисление: 1-й уровень",
        level_2: "Реферальное начисление: 2-й уровень",
        level_3: "Реферальное начисление: 3-й уровень",
        level_4: "Реферальное начисление: 4-й уровень",
        receipt: "Чек зачёта предоплаты",
        payment: "Платёж",
        queued: "В очереди",
        waiting_refund: "Ожидает завершения возврата",
        retry_required: "Нужна повторная проверка",
        review_required: "Нужна сверка с ЮKassa",
        succeeded: "Успешно",
        canceled: "Отменено",
        skipped: "Не требуется",
        creating: "Создаётся",
        partner: "Партнёрское начисление",
        legacy_opening: "Исторический остаток",
        adjustment: "Корректировка",
        refund: "Возврат",
        reward: "Вознаграждение",
    })[String(value)] || String(value ?? "—");
export const inputClass = "w-full rounded-lg border border-border bg-white p-2 text-sm";
export const buttonClass = "rounded-lg bg-primary px-4 py-2 text-sm text-white disabled:opacity-40";
export function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4 rounded-2xl border border-border bg-white p-5">
            <h2 className="text-xl text-primary">{title}</h2>
            {children}
        </section>
    );
}
export function Field({ name, children }: { name: string; children: ReactNode }) {
    return (
        <label className="block space-y-1 text-sm">
            <span>{name}</span>
            {children}
        </label>
    );
}
export function LinkQR({ url: rawUrl }: { url: string }) {
    const url = new URL(rawUrl, window.location.origin).href;
    const [qr, setQr] = useState("");
    const [notice, setNotice] = useState("");
    useEffect(() => {
        void QRCode.toDataURL(url, { width: 180, margin: 2 })
            .then(setQr)
            .catch(() => setNotice("Не удалось создать QR"));
    }, [url]);
    return (
        <div className="flex flex-wrap items-center gap-4">
            {qr && <img src={qr} alt="QR-код ссылки" width={180} height={180} />}
            <div className="min-w-0 flex-1 space-y-2">
                <a className="break-all text-primary underline" href={url}>
                    {url}
                </a>
                <div>
                    <button
                        type="button"
                        className={buttonClass}
                        onClick={() =>
                            void navigator.clipboard
                                .writeText(url)
                                .then(() => setNotice("Скопировано"))
                                .catch(() => setNotice("Выделите и скопируйте ссылку"))
                        }
                    >
                        Скопировать ссылку
                    </button>
                </div>
                <p role="status">{notice}</p>
            </div>
        </div>
    );
}
export function Rows({
    rows,
    columns,
    actions,
}: {
    rows: Row[];
    columns: [string, string][];
    actions?: (row: Row) => ReactNode;
}) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
                <thead>
                    <tr>
                        {columns.map(([key, title]) => (
                            <th key={key} className="whitespace-nowrap border-b p-3">
                                {title}
                            </th>
                        ))}
                        {actions && <th className="p-3">Действия</th>}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, i) => (
                        <tr key={String(row.id ?? i)}>
                            {columns.map(([key]) => (
                                <td key={key} className="border-b p-3">
                                    {key === "status" && row[key] === "paid" && "goods_minor" in row ? (
                                        "Оплачено"
                                    ) : key.endsWith("_minor") ? (
                                        money(row[key])
                                    ) : key === "details" ||
                                      key === "rules" ||
                                      key === "request" ||
                                      key === "payload" ? (
                                        <details>
                                            <summary>Подробности</summary>
                                            <pre className="max-w-xs whitespace-pre-wrap break-all text-xs">
                                                {JSON.stringify(row[key], null, 2)}
                                            </pre>
                                            <button
                                                onClick={() =>
                                                    void navigator.clipboard.writeText(
                                                        JSON.stringify(row[key], null, 2),
                                                    )
                                                }
                                            >
                                                Копировать
                                            </button>
                                        </details>
                                    ) : ["is_partner","is_referral","isReferral"].includes(key) ? (Number(row[key]) ? "Да" : "Нет") : typeof row[key] === "boolean" ? (
                                        row[key] ? (
                                            "Да"
                                        ) : (
                                            "Нет"
                                        )
                                    ) : (
                                        label(row[key])
                                    )}
                                </td>
                            ))}
                            {actions && <td className="space-y-2 border-b p-3">{actions(row)}</td>}
                        </tr>
                    ))}
                </tbody>
            </table>
            {!rows.length && <p className="p-4 text-sm text-muted-foreground">Записей пока нет</p>}
        </div>
    );
}
export function Pager({ page, setPage, hasMore }: { page: number; setPage: (n: number) => void; hasMore: boolean }) {
    return (
        <div className="flex items-center gap-4">
            <button className={buttonClass} disabled={page === 1} onClick={() => setPage(page - 1)}>
                Назад
            </button>
            <span>Страница {page}</span>
            <button className={buttonClass} disabled={!hasMore} onClick={() => setPage(page + 1)}>
                Далее
            </button>
        </div>
    );
}
