import { useState, type FormEvent } from "react";
import { request } from "../api/client";
import { Section, Field, money, bonus, inputClass, buttonClass, type Rates, type Row } from "../../program/shared";

export type ProgramAdminSettings = Rates & { bonusSpendLimitPercent: number };

type Setting = { key: keyof ProgramAdminSettings; title: string; paths: string[]; hint: string; example: string; divisor: number; max?: number; min?: number; step?: string };
const percent = (bps: number) => new Intl.NumberFormat("ru-RU").format(bps / 100);
const scenarios = {
    purchase: { title: "Участник без приглашения", path: "Бонусы за собственную покупку", buyer: "Участник программы" },
    referral: { title: "Приглашённый участник", path: "Бонусы приглашённому и тому, кто его пригласил", buyer: "Приглашённый участник" },
    guest_referral: { title: "Гость по приглашению", path: "Бонусы прямому пригласившему", buyer: "Гость" },
};
type Scenario = keyof typeof scenarios;

export function ProgramSettings({ rates, busy, onSave }: { rates: ProgramAdminSettings; busy: boolean; onSave: (body: Record<string, unknown>) => Promise<unknown> }) {
    const [scenario, setScenario] = useState<Scenario>("referral");
    const [simulation, setSimulation] = useState<{ data: Row; scenario: Scenario } | null>(null);
    const [calculating, setCalculating] = useState(false);
    const [error, setError] = useState("");
    const groups: { title: string; description: string; fields: Setting[] }[] = [
        { title: "Начисление и списание", description: "Бонусы доступны для скидки в течение одного календарного месяца после окончания удержания.", fields: [
            { key: "buyerBps", title: "За собственную покупку, %", paths: ["Участник программы → Его заказ"], hint: "Бонусы начисляются на баланс того, кто оформил заказ.", example: `За покупку на 10 000 ₽: ${bonus(rates.buyerBps * 100)} (${percent(rates.buyerBps)}%).`, divisor: 100, max: 100 },
            { key: "referralBonusBps", title: "За покупку прямого реферала, %", paths: ["Приглашённый → Тот, кто его пригласил"], hint: "Бонусы получает тот, кто поделился приглашением напрямую. Ставка одинакова для всех.", example: `За покупку реферала на 10 000 ₽: ${bonus(rates.referralBonusBps * 100)}.`, divisor: 100, max: 100 },
            { key: "bonusSpendLimitPercent", title: "Лимит списания, %", paths: ["Скидка на товары в заказе"], hint: "Списание ограничено балансом и долей стоимости товаров после скидок. Доставка оплачивается отдельно.", example: `Для товаров на 10 000 ₽: до ${bonus(rates.bonusSpendLimitPercent * 10000)}.`, divisor: 1, max: 100, step: "1" },
            { key: "holdDays", title: "Удержание после доставки, дней", paths: ["Оплата → Доставка → Бонусы доступны"], hint: "Через сколько дней после доставки можно списать бонусы.", example: `По текущим настройкам: через ${rates.holdDays} дней после доставки.`, divisor: 1, max: 3650, step: "1" },
        ] },
    ];
    async function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const body = Object.fromEntries(groups.flatMap((group) => group.fields).map((field) => [field.key, Math.round(Number(form.get(field.key)) * field.divisor)]));
        try { await onSave(body); } catch { /* Parent displays save errors. */ }
    }
    async function simulate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        setCalculating(true); setError(""); setSimulation(null);
        try {
            const data = await request<Row>("/admin/api/program/simulate", { method: "POST", body: {
                scenario, amount: String(form.get("amount")), discountAmount: String(form.get("discount") || "0"),
                bonusAmount: String(form.get("spent") || "0"), costAmount: String(form.get("costs") || ""),
            } });
            setSimulation({ data, scenario });
        } catch (e) { setError(String(e)); } finally { setCalculating(false); }
    }
    const example = scenarios[scenario];
    const resultExample = simulation ? scenarios[simulation.scenario] : null;
    return <div className="space-y-6">
        <form onSubmit={(event) => void save(event)} className="space-y-6">
            <p className="rounded-xl bg-secondary/50 p-4 text-sm leading-relaxed">Проценты считаются от оплаченных товаров после скидок и списания бонусов, без доставки. Ниже — примеры по сохранённым ставкам для покупки на 10 000 ₽.</p>
            {groups.map((group) => <Section key={group.title} title={group.title}>
                <p className="text-sm text-muted-foreground">{group.description}</p>
                <div className="grid gap-5 xl:grid-cols-2">
                    {group.fields.map((field) => <div key={field.key} className="flex flex-col rounded-xl border border-border p-5">
                        <div className="mb-4 space-y-1 rounded-lg bg-secondary/50 p-3 text-sm text-primary">
                            {field.paths.map((path) => <p key={path}>{path}</p>)}
                        </div>
                        <Field name={field.title}>
                            <input name={field.key} className={inputClass} type="number" min={field.min ?? 0} max={field.max} step={field.step ?? "0.01"} defaultValue={rates[field.key] / field.divisor} aria-describedby={`setting-${field.key}-hint`} required />
                        </Field>
                        <p id={`setting-${field.key}-hint`} className="mt-3 text-sm leading-relaxed text-muted-foreground">{field.hint}</p>
                        <p className="mt-3 text-sm leading-relaxed">{field.example}</p>
                    </div>)}
                </div>
            </Section>)}
            <button className={buttonClass} disabled={busy}>Сохранить настройки</button>
        </form>
        <Section title="Проверить на примере заказа">
            <p className="text-sm text-muted-foreground">Выберите, кто покупает, и введите стоимость товаров. Расчёт использует сохранённые ставки, ничего не начисляет и не создаёт заказ. Пример предполагает, что бонусы и реферальные начисления включены.</p>
            <form onSubmit={(event) => void simulate(event)} onChange={() => setSimulation(null)} className="space-y-5">
                <div className="grid gap-5 lg:grid-cols-2">
                    <Field name="Кто совершает покупку?">
                        <select className={inputClass} value={scenario} onChange={(event) => setScenario(event.target.value as Scenario)}>
                            {Object.entries(scenarios).map(([key, item]) => <option key={key} value={key}>{item.title}</option>)}
                        </select>
                    </Field>
                    <Field name="Товары в заказе до скидок, ₽">
                        <input className={inputClass} name="amount" type="number" min="0.01" step="0.01" defaultValue="10000" required />
                        <span className="block text-xs text-muted-foreground">Например, 10 000 ₽. Стоимость доставки сюда не включайте.</span>
                    </Field>
                </div>
                <div className="rounded-xl bg-secondary/50 p-4">
                    <p className="text-sm text-primary">{example.path}</p>
                    <p className="mt-2 text-sm">Покупает: <strong>{example.buyer}</strong>.</p>
                </div>
                <details className="rounded-xl border border-border p-4">
                    <summary className="cursor-pointer text-sm text-primary">Добавить скидку, списанные бонусы и расходы</summary>
                    <div className="mt-4 grid gap-5 lg:grid-cols-3">
                        <Field name="Скидка на товары, ₽">
                            <input className={inputClass} name="discount" type="number" min="0" step="0.01" placeholder="0" />
                            <span className="block text-xs text-muted-foreground">Сумма скидки, не процент. Например, скидка 10% с 10 000 ₽ — это 1 000 ₽.</span>
                        </Field>
                        <Field name="Оплачено старыми бонусами, ₽">
                            <input className={inputClass} name="spent" type="number" min="0" step="0.01" placeholder="0" />
                            <span className="block text-xs text-muted-foreground">Сумма бонусов, потраченных на этот заказ. Например, 500 ₽.</span>
                        </Field>
                        <Field name="Себестоимость и расходы, ₽ (необязательно)">
                            <input className={inputClass} name="costs" type="number" min="0" step="0.01" placeholder="Например, 5 000" />
                            <span className="block text-xs text-muted-foreground">Товары, упаковка, эквайринг и другие ваши расходы. Скидку и бонусы повторно не включайте.</span>
                        </Field>
                    </div>
                </details>
                <button className={buttonClass} disabled={calculating || busy}>{calculating ? "Считаем…" : "Показать, кто сколько получит"}</button>
            </form>
            {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
            {simulation && resultExample && <div role="status" className="space-y-5 rounded-xl border border-primary/20 bg-secondary/20 p-5">
                <div>
                    <h3 className="text-lg text-primary">Кто сколько получит</h3>
                    <p className="mt-1 text-sm">{resultExample.path}</p>
                    <p className="mt-3 text-sm">Оплачено за товары: {money(simulation.data.grossMinor)} − скидка {money(simulation.data.discountMinor)} − старые бонусы {money(simulation.data.spentBonusMinor)} = <strong>{money(simulation.data.basisMinor)}</strong>. От этой суммы считаются проценты.</p>
                </div>
                <dl className="divide-y divide-border">
                    {[
                        ["Прямому пригласившему", simulation.data.referralBonusMinor],
                        ["За собственную покупку", simulation.data.buyerMinor],
                    ].map(([title, amount]) => <div key={String(title)} className="flex flex-wrap items-center justify-between gap-3 py-3">
                        <dt className="text-sm">{String(title)}</dt>
                        <dd className="font-semibold text-primary">{bonus(amount)}</dd>
                    </div>)}
                    <div className="flex justify-between gap-3 py-3 font-semibold"><dt>Всего новых бонусов</dt><dd>{bonus(simulation.data.totalMinor)}</dd></div>
                </dl>
                <p className="text-sm">Остаток после вознаграждений, до себестоимости и расходов: <strong>{money(simulation.data.remainingBeforeCostsMinor)}</strong>. Это ещё не прибыль.</p>
                {simulation.data.remainingAfterCostsMinor !== undefined && <p className="text-sm">После указанных вами расходов ({money(simulation.data.costMinor)}) остаётся <strong>{money(simulation.data.remainingAfterCostsMinor)}</strong>.</p>}
                <p className="text-xs text-muted-foreground">Начисление появится после оплаты. Бонусы станут доступны после доставки и ещё {rates.holdDays} дней ожидания.</p>
            </div>}
        </Section>
    </div>;
}
