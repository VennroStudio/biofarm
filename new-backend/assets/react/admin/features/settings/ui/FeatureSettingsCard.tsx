import { ShoppingCart, UserPlus } from 'lucide-react';
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
    <div className="flex items-center justify-between rounded-lg border border-[#e4e5da] px-4 py-4">
      <div>
        <p className="flex items-center gap-2 font-semibold">
          <Icon className="h-4 w-4" />
          {label}
        </p>
        <p className="text-sm text-[#789083]">{description}</p>
      </div>
      <button
        type="button"
        className={`relative h-7 w-12 rounded-full transition ${checked ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
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
      </div>
    </Card>
  );
}
