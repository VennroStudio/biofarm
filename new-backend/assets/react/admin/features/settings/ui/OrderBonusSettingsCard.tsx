import { Card, Field, inputClass } from "../../../shared/ui";
import type { Settings } from "../../../types";
export function OrderBonusSettingsCard({
    settings,
    onChange,
}: {
    settings: Settings;
    onChange: (s: Settings) => void;
}) {
    return (
        <Card className="space-y-4 p-6">
            <h2 className="text-xl">Покупательские бонусы</h2>
            <a className="underline" href="/admin/program">
                Ставки начисления, удержание и исключения — в правилах партнёрской программы
            </a>
            <p>Приветственные начисления не предусмотрены.</p>
            <Field label="Лимит списания бонусов, %">
                <input
                    className={inputClass}
                    type="number"
                    min="0"
                    max="100"
                    value={settings.order_bonus_spend_limit_percent}
                    onChange={(e) => onChange({ ...settings, order_bonus_spend_limit_percent: Number(e.target.value) })}
                />
            </Field>
        </Card>
    );
}
