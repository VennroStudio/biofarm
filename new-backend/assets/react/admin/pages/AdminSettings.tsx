import { Save } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { settingsApi } from '../api/resources';
import { DeliverySettingsCard } from '../features/settings/ui/DeliverySettingsCard';
import { FeatureSettingsCard } from '../features/settings/ui/FeatureSettingsCard';
import { OrderBonusSettingsCard } from '../features/settings/ui/OrderBonusSettingsCard';
import { PasswordSettingsCard, type PasswordForm } from '../features/settings/ui/PasswordSettingsCard';
import { ReferralSettingsCard } from '../features/settings/ui/ReferralSettingsCard';
import { SeoSettingsCard } from '../features/settings/ui/SeoSettingsCard';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Button, ErrorAlert, PageHeader } from '../shared/ui';
import type { Settings } from '../types';

const defaults: Settings = {
  referral_percent: 5,
  registration_enabled: false,
  cart_enabled: false,
  referral_enabled: false,
  withdrawals_enabled: false,
  favorites_enabled: true,
  order_bonus_enabled: true,
  order_bonus_percent: 5,
  order_bonus_spend_limit_percent: 30,
  welcome_bonus_enabled: false,
  welcome_bonus_amount: 0,
  promo_codes_enabled: false,
  free_delivery_threshold: 3000,
  cdek_delivery_price: 350,
  post_delivery_price: 250,
  order_emails_enabled: false,
  yandex_metrika_enabled: false,
  yandex_metrika_id: '',
  site_name: 'БИОФАРМ',
  site_phone: '+7 (999) 123-45-67',
  site_email: 'bio.active@bk.ru',
  site_logo_url: '/uploads/images/logo.png',
  site_default_og_image: '/assets/images/og/default.jpg',
  site_address_country: 'RU',
  site_address_region: 'Томская область',
  site_address_locality: 'Томск',
  site_address_street: 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
  robots_extra_disallow: '',
};

const emptyPassword: PasswordForm = {
  current: '',
  next: '',
  confirm: '',
};

export function AdminSettings() {
  const [settings, setSettings] = useState<Settings>(defaults);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);
  const [password, setPassword] = useState<PasswordForm>(emptyPassword);
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [passwordSaved, setPasswordSaved] = useState(false);
  const [passwordSaving, setPasswordSaving] = useState(false);

  useLoadOnMount(async () => {
    setSettings(await settingsApi.get());
  });

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      await settingsApi.update(settings);
      setSaved(true);
      window.setTimeout(() => setSaved(false), 1800);
    } catch (saveError) {
      setError(messageFromError(saveError, 'Не удалось сохранить настройки'));
    } finally {
      setSaving(false);
    }
  }

  async function submitPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPasswordError(null);
    setPasswordSaved(false);

    if (password.next !== password.confirm) {
      setPasswordError('Подтверждение пароля не совпадает');
      return;
    }

    setPasswordSaving(true);
    try {
      await settingsApi.changePassword({
        current_password: password.current,
        new_password: password.next,
        confirm_password: password.confirm,
      });
      setPassword(emptyPassword);
      setPasswordSaved(true);
      window.setTimeout(() => setPasswordSaved(false), 1800);
    } catch (changeError) {
      setPasswordError(messageFromError(changeError, 'Не удалось изменить пароль'));
    } finally {
      setPasswordSaving(false);
    }
  }

  return (
    <>
      <PageHeader title="Настройки" subtitle="Конфигурация магазина и бонусной программы" />

      <form className="space-y-6" onSubmit={(event) => void submit(event)}>
        <ErrorAlert>{error}</ErrorAlert>
        <FeatureSettingsCard settings={settings} onChange={setSettings} />
        <SeoSettingsCard settings={settings} onChange={setSettings} />
        <ReferralSettingsCard settings={settings} onChange={setSettings} />
        <OrderBonusSettingsCard settings={settings} onChange={setSettings} />
        <DeliverySettingsCard settings={settings} onChange={setSettings} />

        <div className="flex items-center justify-end gap-3">
          {saved && <span className="text-sm font-semibold text-[#2f7d4b]">Настройки сохранены</span>}
          <Button type="submit" disabled={saving}>
            <Save className="h-4 w-4" />
            {saving ? 'Сохранение...' : 'Сохранить все настройки'}
          </Button>
        </div>
      </form>

      <div className="mt-6">
        <PasswordSettingsCard
          password={password}
          error={passwordError}
          saved={passwordSaved}
          saving={passwordSaving}
          setPassword={setPassword}
          onSubmit={(event) => void submitPassword(event)}
        />
      </div>
    </>
  );
}
