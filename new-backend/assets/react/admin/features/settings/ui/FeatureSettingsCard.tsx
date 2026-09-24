import { FlaskConical, Heart, ShoppingCart, UserPlus, Users, Wallet } from 'lucide-react';
import type { ComponentType } from 'react';
import { Card } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

type ToggleProps = {
  checked: boolean;
  label: string;
  description: string;
  icon: ComponentType<{ className?: string }>;
  onToggle: () => void;
};

function FeatureToggle({ checked, label, description, icon: Icon, onToggle }: ToggleProps) {
  return (
    <div className="flex items-center justify-between gap-4 rounded-lg border border-[#dfece9] px-4 py-4">
      <div>
        <p className="flex items-center gap-2 font-semibold">
          <Icon className="h-4 w-4" />
          {label}
        </p>
        <p className="text-sm text-[#5f7580]">{description}</p>
      </div>
      <button
        type="button"
        className={`relative h-7 w-12 shrink-0 rounded-full transition ${checked ? 'bg-[#2e8175]' : 'bg-[#cfe2de]'}`}
        onClick={onToggle}
        aria-pressed={checked}
        aria-label={label}
      >
        <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${checked ? 'left-6' : 'left-1'}`} />
      </button>
    </div>
  );
}

export function FeatureSettingsCard({ settings, onChange }: Props) {
  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold">Функции сайта</h2>
      </div>
      <div className="grid gap-4">
        <FeatureToggle
          checked={settings.cart_enabled}
          label="Корзина и заказы"
          description="Показывает корзину, оформление заказа, страницу успешного заказа и кнопку добавления в корзину."
          icon={ShoppingCart}
          onToggle={() => onChange({ ...settings, cart_enabled: !settings.cart_enabled })}
        />
        <FeatureToggle
          checked={settings.registration_enabled}
          label="Регистрация"
          description="Показывает вкладку регистрации в личном кабинете."
          icon={UserPlus}
          onToggle={() => onChange({ ...settings, registration_enabled: !settings.registration_enabled })}
        />
        <FeatureToggle
          checked={settings.referral_enabled}
          label="Реферальная программа"
          description="Показывает партнёрский блок в профиле и начисляет реферальные бонусы только при включенных заказах."
          icon={Users}
          onToggle={() => onChange({ ...settings, referral_enabled: !settings.referral_enabled })}
        />
        <FeatureToggle
          checked={settings.withdrawals_enabled}
          label="Заявки на вывод"
          description="Показывает вывод средств партнёрам только при включенной реферальной программе."
          icon={Wallet}
          onToggle={() => onChange({ ...settings, withdrawals_enabled: !settings.withdrawals_enabled })}
        />
        <FeatureToggle
          checked={settings.favorites_enabled}
          label="Избранное"
          description="Показывает избранные товары и кнопки добавления в избранное."
          icon={Heart}
          onToggle={() => onChange({ ...settings, favorites_enabled: !settings.favorites_enabled })}
        />
        <FeatureToggle
          checked={settings.testing_enabled}
          label="Тестирование"
          description="Не включать. Только для тестирования процессов."
          icon={FlaskConical}
          onToggle={() => onChange({ ...settings, testing_enabled: !settings.testing_enabled })}
        />
      </div>
    </Card>
  );
}
