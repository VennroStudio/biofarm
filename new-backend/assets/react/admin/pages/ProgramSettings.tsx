import { useState, type FormEvent } from "react";
import { request } from "../api/client";
import { Section, Field, money, inputClass, buttonClass, type Rates, type Row } from "../../program/shared";

type Setting = { key: keyof Rates; title: string; paths: string[]; hint: string; example: string; divisor: number; max?: number; min?: number; step?: string };
const percent = (bps: number) => new Intl.NumberFormat("ru-RU").format(bps / 100);
const scenarios = {
    partner_customer: { title: "Реферал партнёра покупает", path: "Петя (партнёр) → Сергей (реферал)", buyer: "Сергей", direct: "Пете — комиссия за своего реферала", partner: "", bonus: "" },
    member_purchase: { title: "Участник команды покупает для себя", path: "Петя (партнёр) → Алёна (участник команды)", buyer: "Алёна", direct: "", partner: "Пете — комиссия с личной покупки Алёны", bonus: "" },
    team_customer: { title: "Реферал участника команды покупает", path: "Петя (партнёр) → Алёна (участник) → Кирилл (реферал)", buyer: "Кирилл", direct: "Алёне — комиссия за своего реферала", partner: "Пете — комиссия за реферала участника команды", bonus: "" },
    ordinary_referral: { title: "Приглашённый обычным покупателем делает заказ", path: "Кирилл (обычный покупатель) → его друг", buyer: "Друг Кирилла", direct: "", partner: "", bonus: "Кириллу — бонусы за приглашённого друга" },
};
type Scenario = keyof typeof scenarios;

export function ProgramSettings({ rates, busy, onSave }: { rates: Rates; busy: boolean; onSave: (body: Record<string, unknown>) => Promise<unknown> }) {
    const [scenario, setScenario] = useState<Scenario>("team_customer");
    const [simulation, setSimulation] = useState<{ data: Row; scenario: Scenario } | null>(null);
    const [calculating, setCalculating] = useState(false);
    const [error, setError] = useState("");
    const groups: { title: string; description: string; fields: Setting[] }[] = [
        { title: "Комиссии", description: "Деньги партнёрам и участникам команды. Их можно запросить к выплате.", fields: [
            { key: "directBps", title: "Комиссия пригласившему за прямую покупку, %", paths: ["Партнёр → Реферал: получает партнёр", "Партнёр → Участник команды: получает партнёр", "Участник команды → Реферал: получает участник"], hint: "Одна ставка для всех трёх случаев. Покупает человек справа от стрелки.", example: `Покупка 10 000 ₽ → пригласившему ${money(rates.directBps * 100)} (${percent(rates.directBps)}%).`, divisor: 100, max: 100 },
            { key: "teamBps", title: "Комиссия партнёру с покупок рефералов его команды, %", paths: ["Партнёр → Участник команды → Реферал"], hint: "Покупает реферал. Эту комиссию получает партнёр; участник отдельно получает прямую комиссию по соседнему полю.", example: `Кирилл покупает на 10 000 ₽ → Пете ${money(rates.teamBps * 100)}, Алёне ${money(rates.directBps * 100)}.`, divisor: 100, max: 100 },
            { key: "capBps", title: "Максимум комиссий и новых бонусов с заказа, %", paths: ["Один заказ → все денежные комиссии + все новые бонусы"], hint: "Защита бюджета: система не сохранит ставки, если их сумма в любом сценарии выше лимита. Лимит не является дополнительным начислением.", example: `При лимите ${percent(rates.capBps)}% с 10 000 ₽ можно начислить суммарно до ${money(rates.capBps * 100)}. Скидки и списанные бонусы в этот лимит не входят.`, divisor: 100, max: 100 },
        ] },
        { title: "Бонусы", description: "Баллы для покупок в магазине. Их нельзя вывести деньгами.", fields: [
            { key: "referralBonusBps", title: "Бонусы обычному покупателю за приглашённого, %", paths: ["Обычный покупатель → Его реферал: бонусы пригласившему"], hint: "Кирилл не является участником команды. Когда покупает его друг, Кирилл получает бонусы; Петя и Алёна ничего не получают.", example: `Друг покупает на 10 000 ₽ → Кириллу ${money(rates.referralBonusBps * 100)} бонусами.`, divisor: 100, max: 100 },
            { key: "buyerBps", title: "Бонусы за собственную покупку, %", paths: ["Зарегистрированный покупатель → Его оплаченный заказ"], hint: "Получает сам покупатель, независимо от того, кто его пригласил. При покупке без регистрации эти бонусы не начисляются.", example: `Покупка 10 000 ₽ → покупателю ${money(rates.buyerBps * 100)} бонусами, если бонусы за заказ включены.`, divisor: 100, max: 100 },
        ] },
        { title: "Выплаты", description: "Когда начисления становятся доступны и какую сумму можно запросить к выводу. Денежный перевод выполняет администратор вручную.", fields: [
            { key: "holdDays", title: "Ожидание после доставки, дней", paths: ["Оплата → Доставка → Ожидание → Доступный баланс"], hint: "Срок начинается после подтверждения доставки. Применяется и к комиссиям, и к покупательским бонусам.", example: `При значении 14 начисление станет доступно через 14 дней после доставки.`, divisor: 1, max: 3650, step: "1" },
            { key: "minimumWithdrawalMinor", title: "Минимальная сумма заявки на выплату, ₽", paths: ["Партнёр или участник → Заявка → Ручной перевод"], hint: "Порог для одной заявки. На доступном денежном балансе должно быть не меньше запрошенной суммы.", example: "При минимуме 100 ₽ заявку на 90 ₽ отправить нельзя, на 100 ₽ — можно.", divisor: 100, min: 0.01 },
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
            <p className="text-sm text-muted-foreground">Выберите, кто покупает, и введите стоимость товаров. Расчёт использует сохранённые ставки, ничего не начисляет и не создаёт заказ. Пример предполагает зарегистрированного покупателя и включённые бонусы за заказ.</p>
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
                            <span className="block text-xs text-muted-foreground">Сколько бонусов покупатель потратил на этот заказ. Например, 500 ₽. Новые бонусы здесь не указывайте.</span>
                        </Field>
                        <Field name="Себестоимость и расходы, ₽ (необязательно)">
                            <input className={inputClass} name="costs" type="number" min="0" step="0.01" placeholder="Например, 5 000" />
                            <span className="block text-xs text-muted-foreground">Товары, упаковка, эквайринг и другие ваши расходы. Скидку, бонусы и комиссии программы повторно не включайте.</span>
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
                        [resultExample.direct, simulation.data.directMinor, "Деньги на вывод"],
                        [resultExample.partner, simulation.data.partnerMinor, "Деньги на вывод"],
                        [resultExample.bonus, simulation.data.referralBonusMinor, "Бонусы на покупки"],
                        [`${resultExample.buyer} — бонусы за собственную покупку`, simulation.data.buyerMinor, "Бонусы на покупки"],
                    ].filter(([title]) => title).map(([title, amount, wallet]) => <div key={String(title)} className="flex flex-wrap items-center justify-between gap-3 py-3">
                        <dt className="text-sm">{String(title)}<span className="mt-1 block text-xs text-muted-foreground">{String(wallet)}</span></dt>
                        <dd className="font-semibold text-primary">{money(amount)}</dd>
                    </div>)}
                    <div className="flex justify-between gap-3 py-3 font-semibold"><dt>Всего комиссий и новых бонусов</dt><dd>{money(simulation.data.totalMinor)}</dd></div>
                </dl>
                {simulation.scenario === "ordinary_referral" && <p className="text-sm">Петя и Алёна с этого заказа ничего не получают.</p>}
                <p className="text-sm">Остаток после вознаграждений, до себестоимости и расходов: <strong>{money(simulation.data.remainingBeforeCostsMinor)}</strong>. Это ещё не прибыль.</p>
                {simulation.data.remainingAfterCostsMinor !== undefined && <p className="text-sm">После указанных вами расходов ({money(simulation.data.costMinor)}) остаётся <strong>{money(simulation.data.remainingAfterCostsMinor)}</strong>.</p>}
                <p className="text-xs text-muted-foreground">Начисление появится после оплаты. Деньги и бонусы станут доступны после доставки и ещё {rates.holdDays} дней ожидания.</p>
            </div>}
        </Section>
    </div>;
}
