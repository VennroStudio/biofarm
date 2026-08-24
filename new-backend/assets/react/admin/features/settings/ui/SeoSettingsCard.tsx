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

      <div className="mt-6">
        <div className="mb-6 rounded-lg border border-[#e4e5da] p-4">
          <div className="mb-4">
            <p className="font-semibold">SEO-шаблоны</p>
            <p className="text-sm text-[#789083]">
              Используются только когда у товара, категории или атрибута не заполнено собственное SEO.
              Доступные переменные: {'{name}'}, {'{h1}'}, {'{category}'}, {'{price}'}, {'{weight}'}, {'{slug}'}.
            </p>
          </div>

          <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <Field label="Title товара">
              <input
                className={inputClass}
                value={settings.seo_product_title_template}
                onChange={(event) => set('seo_product_title_template', event.target.value)}
              />
            </Field>
            <Field label="Description товара">
              <textarea
                className={textareaClass}
                value={settings.seo_product_description_template}
                onChange={(event) => set('seo_product_description_template', event.target.value)}
              />
            </Field>
            <Field label="Title категории">
              <input
                className={inputClass}
                value={settings.seo_category_title_template}
                onChange={(event) => set('seo_category_title_template', event.target.value)}
              />
            </Field>
            <Field label="Description категории">
              <textarea
                className={textareaClass}
                value={settings.seo_category_description_template}
                onChange={(event) => set('seo_category_description_template', event.target.value)}
              />
            </Field>
            <Field label="Title SEO-фильтра">
              <input
                className={inputClass}
                value={settings.seo_attribute_title_template}
                onChange={(event) => set('seo_attribute_title_template', event.target.value)}
              />
            </Field>
            <Field label="Description SEO-фильтра">
              <textarea
                className={textareaClass}
                value={settings.seo_attribute_description_template}
                onChange={(event) => set('seo_attribute_description_template', event.target.value)}
              />
            </Field>
          </div>
        </div>

        <div className="mb-5 flex items-center justify-between rounded-lg border border-[#e4e5da] px-4 py-4">
          <div>
            <p className="font-semibold">Яндекс.Метрика</p>
            <p className="text-sm text-[#789083]">Подключает счетчик, если указан ID.</p>
          </div>
          <button
            aria-label="Переключить Яндекс.Метрику"
            aria-pressed={settings.yandex_metrika_enabled}
            className={`relative h-7 w-12 rounded-full transition ${settings.yandex_metrika_enabled ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
            type="button"
            onClick={() => set('yandex_metrika_enabled', !settings.yandex_metrika_enabled)}
          >
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.yandex_metrika_enabled ? 'left-6' : 'left-1'}`} />
          </button>
        </div>

        {settings.yandex_metrika_enabled && (
          <div className="mb-5 max-w-sm">
            <Field label="ID счетчика Яндекс.Метрики">
              <input
                className={inputClass}
                value={settings.yandex_metrika_id}
                onChange={(event) => set('yandex_metrika_id', event.target.value)}
              />
            </Field>
          </div>
        )}

        <Field label={<span className="flex items-center gap-2"><FileText className="h-4 w-4" />Дополнительные Disallow в robots.txt</span>}>
          <textarea
            className={`${textareaClass} min-h-32`}
            value={settings.robots_extra_disallow}
            onChange={(event) => set('robots_extra_disallow', event.target.value)}
            placeholder="/example&#10;/private"
          />
        </Field>
      </div>
    </Card>
  );
}
