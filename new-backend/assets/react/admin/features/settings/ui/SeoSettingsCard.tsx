import { FileText, Image, Mail, MapPin, Phone, Search } from 'lucide-react';
import { Card, Field, inputClass, textareaClass } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

export function SeoSettingsCard({ settings, onChange }: Props) {
  const set = (key: keyof Settings, value: Settings[keyof Settings]) => onChange({ ...settings, [key]: value });

  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <Search className="h-5 w-5" />
          SEO и контакты
        </h2>
        <p className="text-sm text-[#789083]">Основные данные для Open Graph и Organization JSON-LD</p>
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Field label="Название сайта">
          <input
            className={inputClass}
            value={settings.site_name}
            onChange={(event) => set('site_name', event.target.value)}
          />
        </Field>
        <Field label={<span className="flex items-center gap-2"><Phone className="h-4 w-4" />Телефон</span>}>
          <input
            className={inputClass}
            value={settings.site_phone}
            onChange={(event) => set('site_phone', event.target.value)}
          />
        </Field>
        <Field label={<span className="flex items-center gap-2"><Mail className="h-4 w-4" />Email</span>}>
          <input
            className={inputClass}
            type="email"
            value={settings.site_email}
            onChange={(event) => set('site_email', event.target.value)}
          />
        </Field>
        <Field label={<span className="flex items-center gap-2"><Image className="h-4 w-4" />OG-изображение по умолчанию</span>}>
          <input
            className={inputClass}
            value={settings.site_default_og_image}
            onChange={(event) => set('site_default_og_image', event.target.value)}
          />
        </Field>
        <Field label="Логотип для JSON-LD">
          <input
            className={inputClass}
            value={settings.site_logo_url}
            onChange={(event) => set('site_logo_url', event.target.value)}
          />
        </Field>
        <Field label={<span className="flex items-center gap-2"><MapPin className="h-4 w-4" />Страна</span>}>
          <input
            className={inputClass}
            value={settings.site_address_country}
            onChange={(event) => set('site_address_country', event.target.value)}
          />
        </Field>
        <Field label="Регион">
          <input
            className={inputClass}
            value={settings.site_address_region}
            onChange={(event) => set('site_address_region', event.target.value)}
          />
        </Field>
        <Field label="Город">
          <input
            className={inputClass}
            value={settings.site_address_locality}
            onChange={(event) => set('site_address_locality', event.target.value)}
          />
        </Field>
        <Field label="Юридический адрес производства">
          <input
            className={inputClass}
            value={settings.site_address_street}
            onChange={(event) => set('site_address_street', event.target.value)}
          />
        </Field>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Field label={<span className="flex items-center gap-2"><FileText className="h-4 w-4" />Дополнительные Disallow в robots.txt</span>}>
          <textarea
            className={`${textareaClass} min-h-32`}
            value={settings.robots_extra_disallow}
            onChange={(event) => set('robots_extra_disallow', event.target.value)}
            placeholder="/example&#10;/private"
          />
        </Field>
        <label className="flex min-h-32 items-start gap-3 rounded-lg border border-[#e4e5da] bg-[#fbfaf4] p-4 text-sm font-semibold text-[#26382d]">
          <input
            className="mt-1"
            type="checkbox"
            checked={settings.sitemap_include_legal_pages}
            onChange={(event) => set('sitemap_include_legal_pages', event.target.checked)}
          />
          <span>
            Добавлять юридические страницы в sitemap
            <span className="mt-1 block font-normal text-[#789083]">
              Включайте только если страницы политики и оферты должны индексироваться.
            </span>
          </span>
        </label>
      </div>
    </Card>
  );
}
