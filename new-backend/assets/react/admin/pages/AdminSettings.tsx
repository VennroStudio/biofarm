import { Save } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { settingsApi } from '../api/resources';
import { FeatureSettingsCard } from '../features/settings/ui/FeatureSettingsCard';
import { OrderBonusSettingsCard } from '../features/settings/ui/OrderBonusSettingsCard';
import { PasswordSettingsCard, type PasswordForm } from '../features/settings/ui/PasswordSettingsCard';
import { ReferralSettingsCard } from '../features/settings/ui/ReferralSettingsCard';
import { SeoSettingsCard } from '../features/settings/ui/SeoSettingsCard';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Button, PageHeader } from '../shared/ui';
import type { Settings } from '../types';

const defaults: Settings = {
  referral_percent: 5,
  registration_enabled: false,
  cart_enabled: false,
  order_bonus_enabled: true,
  order_bonus_percent: 5,
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
  sitemap_include_legal_pages: false,
};

const emptyPassword: PasswordForm = {
  current: '',
  next: '',
  confirm: '',
};

export function AdminSettings() {
  const [settings, setSettings] = useState<Settings>(defaults);
  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);
  const [password, setPassword] = useState<PasswordForm>(emptyPassword);

  useLoadOnMount(async () => {
    setSettings(await settingsApi.get());
  });

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      await settingsApi.update(settings);
      setSaved(true);
      window.setTimeout(() => setSaved(false), 1800);
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <PageHeader title="Настройки" subtitle="Конфигурация магазина и бонусной программы" />

      <form className="space-y-6" onSubmit={(event) => void submit(event)}>
        <FeatureSettingsCard settings={settings} onChange={setSettings} />
        <SeoSettingsCard settings={settings} onChange={setSettings} />
        <ReferralSettingsCard settings={settings} onChange={setSettings} />
        <OrderBonusSettingsCard settings={settings} onChange={setSettings} />
        <PasswordSettingsCard password={password} setPassword={setPassword} />

        <div className="flex items-center justify-end gap-3">
          {saved && <span className="text-sm font-semibold text-[#2f7d4b]">Настройки сохранены</span>}
          <Button type="submit" disabled={saving}>
            <Save className="h-4 w-4" />
            {saving ? 'Сохранение...' : 'Сохранить все настройки'}
          </Button>
        </div>
      </form>
    </>
  );
}
