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
                  ? 'border-[#2f7d4b] bg-[#2f7d4b] text-white'
                  : 'border-[#d9dece] bg-white text-[#53685c] hover:border-[#2f7d4b] hover:text-[#2f7d4b]'
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
      ) : (
        <form className="space-y-6" onSubmit={(event) => void submit(event)}>
          <ErrorAlert>{error}</ErrorAlert>
          {renderSettingsSection(activeSection, settings, setSettings)}

          <div className="flex items-center justify-end gap-3">
            {saved && <span className="text-sm font-semibold text-[#2f7d4b]">Настройки сохранены</span>}
            <Button type="submit" disabled={saving}>
              <Save className="h-4 w-4" />
              {saving ? 'Сохранение...' : 'Сохранить настройки'}
            </Button>
          </div>
        </form>
      )}
    </>
  );
}
