import { PlugZap, Save, Search, ShieldCheck, SlidersHorizontal, Truck } from 'lucide-react';
import { PaymentIntegrationCard } from '../features/settings/ui/PaymentIntegrationCard';
import { AdminIntegrationErrors } from './AdminIntegrationErrors';
import { FormEvent, useState } from 'react';
import { Navigate, NavLink, useParams } from 'react-router-dom';
import { settingsApi } from '../api/resources';
import {
  defaultSettingsSection,
  isSettingsSectionId,
  settingsSections,
  settingsSectionPath,
  type SettingsSectionId,
} from '../features/settings/model/settingsSections';
import { BitrixSettingsCard } from '../features/settings/ui/BitrixSettingsCard';
import { DeliverySettingsCard } from '../features/settings/ui/DeliverySettingsCard';
import { FeatureSettingsCard } from '../features/settings/ui/FeatureSettingsCard';
import { PasswordSettingsCard, type PasswordForm } from '../features/settings/ui/PasswordSettingsCard';
import { SeoSettingsCard } from '../features/settings/ui/SeoSettingsCard';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Button, ErrorAlert, PageHeader } from '../shared/ui';
import type { Settings } from '../types';

const sectionIcons = { features: SlidersHorizontal, seo: Search, integrations: PlugZap, orders: Truck, security: ShieldCheck };

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
  bitrix_widget_enabled: false,
  bitrix_widget_code: '',
  bitrix_crm_enabled: false,
  seo_product_title_template: '{name} — купить натуральный продукт БИОФАРМ',
  seo_product_description_template: '{name}: описание, состав, цена и сертификаты качества. Натуральная продукция БИОФАРМ с доставкой по России.',
  seo_category_title_template: '{h1} — БИОФАРМ',
  seo_category_description_template: 'Каталог продукции БИОФАРМ в категории {name}. Натуральные растительные экстракты и БАДы с доставкой по России.',
  seo_attribute_title_template: '{h1} — БИОФАРМ',
  seo_attribute_description_template: '{h1}: натуральная продукция БИОФАРМ с понятным составом и доставкой по России.',
  site_name: 'БИОФАРМ',
  site_phone: '+7 (999) 123-45-67',
  site_email: 'bio.active@bk.ru',
  site_logo_url: '/uploads/images/logo.png',
  site_default_og_image: '/assets/images/og/default.jpg',
  site_address_country: 'RU',
  site_address_region: 'Томская область',
  site_address_locality: 'Томск',
  site_address_street: 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
  robots_txt: '',
  robots_extra_disallow: '',
};

const emptyPassword: PasswordForm = {
  current: '',
  next: '',
  confirm: '',
};

type SaveableSettingsSectionId = Exclude<SettingsSectionId, 'integrations' | 'security'>;

type WritableSettingsKey = Exclude<keyof Settings, 'bitrix_crm_enabled'>;

const sectionSettingsKeys: Record<SaveableSettingsSectionId, readonly WritableSettingsKey[]> = {
  features: [
    'cart_enabled',
    'registration_enabled',
    'referral_enabled',
    'withdrawals_enabled',
    'favorites_enabled',
  ],
  seo: [
    'site_name',
    'site_phone',
    'site_email',
    'site_logo_url',
    'site_default_og_image',
    'site_address_country',
    'site_address_region',
    'site_address_locality',
    'site_address_street',
    'seo_product_title_template',
    'seo_product_description_template',
    'seo_category_title_template',
    'seo_category_description_template',
    'seo_attribute_title_template',
    'seo_attribute_description_template',
    'yandex_metrika_enabled',
    'yandex_metrika_id',
    'robots_txt',
    'robots_extra_disallow',
  ],
  orders: [
    'free_delivery_threshold',
    'cdek_delivery_price',
    'post_delivery_price',
    'promo_codes_enabled',
    'order_emails_enabled',
  ],
};

function pickSectionSettings(settings: Settings, section: SaveableSettingsSectionId): Partial<Settings> {
  return sectionSettingsKeys[section].reduce<Partial<Settings>>((payload, key) => ({
    ...payload,
    [key]: settings[key],
  }), {});
}

function renderSettingsSection(section: SettingsSectionId, settings: Settings, setSettings: (settings: Settings) => void) {
  switch (section) {
    case 'features':
      return <FeatureSettingsCard settings={settings} onChange={setSettings} />;
    case 'seo':
      return <SeoSettingsCard settings={settings} onChange={setSettings} />;
    case 'integrations':
      return <BitrixSettingsCard settings={settings} onChange={setSettings} />;
    case 'orders':
      return <DeliverySettingsCard settings={settings} onChange={setSettings} />;
    case 'security':
      return null;
  }
}

export function AdminSettings() {
  const { section } = useParams();
  const activeSection = isSettingsSectionId(section) ? section : defaultSettingsSection;
  const sectionMeta = settingsSections.find((item) => item.id === activeSection);
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

  async function submit(event: FormEvent<HTMLFormElement>, sectionId: SaveableSettingsSectionId) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      await settingsApi.update(pickSectionSettings(settings, sectionId));
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

  if (section !== undefined && !isSettingsSectionId(section)) {
    return <Navigate to={settingsSectionPath(defaultSettingsSection)} replace />;
  }

  return (
    <>
      <div className="mb-7">
        <p className="mb-3 text-sm font-semibold text-[#5f7580]">Настройки</p>
        <nav aria-label="Разделы настроек" className="flex flex-wrap gap-2 rounded-2xl border border-[#dfece9] bg-[#f4faf8] p-2">
          {settingsSections.map((item) => {
            const Icon = sectionIcons[item.id];
            return (
              <NavLink key={item.id} to={settingsSectionPath(item.id)}
                className={({ isActive }) => `inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-colors focus-visible:outline-offset-2 focus-visible:outline-[#2e8175] ${isActive ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-white hover:text-[#18574f]'}`}>
                <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />{item.label}
              </NavLink>
            );
          })}
        </nav>
      </div>
      <PageHeader title={sectionMeta?.label ?? 'Настройки'} subtitle={sectionMeta?.subtitle ?? 'Конфигурация магазина'} />

      {activeSection === 'security' ? (
        <PasswordSettingsCard
          password={password}
          error={passwordError}
          saved={passwordSaved}
          saving={passwordSaving}
          setPassword={setPassword}
          onSubmit={(event) => void submitPassword(event)}
        />
      ) : activeSection === 'integrations' ? (
        <div className="space-y-8">
          <PaymentIntegrationCard />
          <BitrixSettingsCard settings={settings} onChange={setSettings} />
          <AdminIntegrationErrors />
        </div>
      ) : (
        <form className="space-y-6" onSubmit={(event) => void submit(event, activeSection)}>
          <ErrorAlert>{error}</ErrorAlert>
          {renderSettingsSection(activeSection, settings, setSettings)}

          <div className="flex items-center justify-end gap-3">
            {saved && <span className="text-sm font-semibold text-[#2e8175]">Вкладка сохранена</span>}
            <Button type="submit" disabled={saving}>
              <Save className="h-4 w-4" />
              {saving ? 'Сохранение...' : 'Сохранить'}
            </Button>
          </div>
        </form>
      )}
    </>
  );
}
