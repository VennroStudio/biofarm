import { Percent, Power, ShoppingBag } from 'lucide-react';
import { Card, Field, inputClass } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

export function OrderBonusSettingsCard({ settings, onChange }: Props) {
  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <ShoppingBag className="h-5 w-5" />
          Бонусы за заказ
        </h2>
        <p className="text-sm text-[#789083]">Настройки начисления бонусов за покупки</p>
      </div>
      <div className="flex items-center justify-between rounded-lg border border-[#e4e5da] px-4 py-4">
        <div>
          <p className="flex items-center gap-2 font-semibold">
            <Power className="h-4 w-4" />
            Начисление бонусов за заказ
          </p>
          <p className="text-sm text-[#789083]">Пользователи получают бонусы за каждый заказ</p>
        </div>
        <button
          type="button"
          className={`relative h-7 w-12 rounded-full transition ${settings.order_bonus_enabled ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
          onClick={() => onChange({ ...settings, order_bonus_enabled: !settings.order_bonus_enabled })}
          aria-label="Переключить бонусы за заказ"
        >
          <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.order_bonus_enabled ? 'left-6' : 'left-1'}`} />
        </button>
      </div>
      {settings.order_bonus_enabled && (
        <div className="mt-5 max-w-xs">
          <Field
            label={(
              <span className="flex items-center gap-2">
                <Percent className="h-4 w-4" />
                Процент бонусов за заказ
              </span>
            )}
          >
            <div className="relative">
              <input
                className={`${inputClass} pr-8`}
                type="number"
                min="0"
                max="100"
                value={settings.order_bonus_percent}
                onChange={(event) => onChange({ ...settings, order_bonus_percent: Number(event.target.value) })}
              />
              <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#789083]">%</span>
            </div>
          </Field>
        </div>
      )}
    </Card>
  );
}
