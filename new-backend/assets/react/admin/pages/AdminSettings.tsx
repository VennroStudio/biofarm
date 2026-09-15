import { Save } from 'lucide-react';
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
import { HomeBlocksSettingsCard } from '../features/settings/ui/HomeBlocksSettingsCard';
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
  home_features_enabled: true,
  home_catalog_enabled: true,
  home_video_enabled: true,
  home_blog_enabled: true,
  home_about_enabled: true,
  home_marketplaces_enabled: true,
  home_certificates_enabled: true,
  home_loyalty_enabled: true,
  home_reviews_enabled: true,
  home_contacts_enabled: true,
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
  home: [
    'home_features_enabled',
    'home_catalog_enabled',
    'home_video_enabled',
    'home_blog_enabled',
    'home_about_enabled',
    'home_marketplaces_enabled',
    'home_certificates_enabled',
    'home_loyalty_enabled',
    'home_reviews_enabled',
    'home_contacts_enabled',
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
  loyalty: [
    'referral_percent',
    'order_bonus_enabled',
    'order_bonus_percent',
    'order_bonus_spend_limit_percent',
    'welcome_bonus_enabled',
    'welcome_bonus_amount',
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
    case 'home':
      return <HomeBlocksSettingsCard settings={settings} onChange={setSettings} />;
    case 'seo':
      return <SeoSettingsCard settings={settings} onChange={setSettings} />;
    case 'integrations':
      return <BitrixSettingsCard settings={settings} onChange={setSettings} />;
    case 'orders':
      return <DeliverySettingsCard settings={settings} onChange={setSettings} />;
    case 'loyalty':
      return (
        <>
          <ReferralSettingsCard settings={settings} onChange={setSettings} />
          <OrderBonusSettingsCard settings={settings} onChange={setSettings} />
        </>
      );
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
      <PageHeader title="Настройки" subtitle={sectionMeta?.subtitle ?? 'Конфигурация магазина'} />

      <div className="mb-6 flex flex-wrap gap-2">
        {settingsSections.map((item) => (
          <NavLink
            key={item.id}
            to={settingsSectionPath(item.id)}
            className={({ isActive }) =>
              `rounded-md border px-4 py-2 text-sm font-semibold transition ${
                isActive
                  ? 'border-[#2e8175] bg-[#2e8175] text-white'
                  : 'border-[#cfe2de] bg-white text-[#526d78] hover:border-[#2e8175] hover:text-[#2e8175]'
              }`
            }
          >
            {item.label}
          </NavLink>
        ))}
      </div>

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
        <BitrixSettingsCard settings={settings} onChange={setSettings} />
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
