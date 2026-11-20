import { Mail, Package, Ticket, Truck } from 'lucide-react';
import { Card, Field, inputClass } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

function toggleClass(checked: boolean) {
  return `relative h-7 w-12 rounded-full transition ${checked ? 'bg-[#2e8175]' : 'bg-[#cfe2de]'}`;
}

export function DeliverySettingsCard({ settings, onChange }: Props) {
  const set = (key: keyof Settings, value: Settings[keyof Settings]) => onChange({ ...settings, [key]: value });

  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <Truck className="h-5 w-5" />
          Доставка и скидки
        </h2>
        <p className="text-sm text-[#5f7580]">Параметры оформления заказа и серверного расчёта стоимости</p>
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <Field label="Бесплатная доставка от">
          <input
            className={inputClass}
            min="0"
            type="number"
            value={settings.free_delivery_threshold}
            onChange={(event) => set('free_delivery_threshold', Number(event.target.value))}
          />
        </Field>
        <Field label="СДЭК">
          <input
            className={inputClass}
            min="0"
            type="number"
            value={settings.cdek_delivery_price}
            onChange={(event) => set('cdek_delivery_price', Number(event.target.value))}
          />
        </Field>
        <Field label="Почта России">
          <input
            className={inputClass}
            min="0"
            type="number"
            value={settings.post_delivery_price}
            onChange={(event) => set('post_delivery_price', Number(event.target.value))}
          />
        </Field>
      </div>

      <div className="mt-5 grid gap-4">
        <div className="flex items-center justify-between rounded-lg border border-[#dfece9] px-4 py-4">
          <div>
            <p className="flex items-center gap-2 font-semibold">
              <Ticket className="h-4 w-4" />
              Промокоды
            </p>
            <p className="text-sm text-[#5f7580]">Показывает поле промокода в оформлении заказа и применяет скидку на бекенде.</p>
          </div>
          <button
            aria-label="Переключить промокоды"
            aria-pressed={settings.promo_codes_enabled}
            className={toggleClass(settings.promo_codes_enabled)}
            type="button"
            onClick={() => set('promo_codes_enabled', !settings.promo_codes_enabled)}
          >
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.promo_codes_enabled ? 'left-6' : 'left-1'}`} />
          </button>
        </div>

        <div className="flex items-center justify-between rounded-lg border border-[#dfece9] px-4 py-4">
          <div>
            <p className="flex items-center gap-2 font-semibold">
              <Mail className="h-4 w-4" />
              Email по заказам
            </p>
            <p className="text-sm text-[#5f7580]">Отправляет покупателю письма при создании и изменении заказа.</p>
          </div>
          <button
            aria-label="Переключить письма по заказам"
            aria-pressed={settings.order_emails_enabled}
            className={toggleClass(settings.order_emails_enabled)}
            type="button"
            onClick={() => set('order_emails_enabled', !settings.order_emails_enabled)}
          >
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.order_emails_enabled ? 'left-6' : 'left-1'}`} />
          </button>
        </div>

        <div className="rounded-lg border border-[#dfece9] px-4 py-4">
          <p className="flex items-center gap-2 font-semibold">
            <Package className="h-4 w-4" />
            Зависимость от корзины
          </p>
          <p className="text-sm text-[#5f7580]">
            Если корзина выключена, промокоды, бонусы за заказ, доставка, оформление заказа и письма по заказам не показываются на сайте.
          </p>
        </div>
      </div>
    </Card>
  );
}
