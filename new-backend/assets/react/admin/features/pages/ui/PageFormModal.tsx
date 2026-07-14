import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { CmsPageTemplate } from '../../../types';
import type { PageForm } from '../model/pageForm';

type Props = {
  form: PageForm;
  open: boolean;
  saving: boolean;
  templates: CmsPageTemplate[];
  setForm: Dispatch<SetStateAction<PageForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function PageFormModal({ form, open, saving, templates, setForm, onClose, onSubmit }: Props) {
  const isSystem = form.page_type === 'system';

  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать страницу' : 'Новая страница'}
      description={isSystem ? 'У системной страницы меняются SEO и параметры видимости. Маршрут и шаблон фиксированы.' : undefined}
      onClose={onClose}
      maxWidth="max-w-4xl"
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-page-form" disabled={saving || !form.title || (!isSystem && !form.slug_path)}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-page-form" className="grid gap-5" onSubmit={onSubmit}>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} />
          </Field>
          <Field label="H1">
            <input className={inputClass} value={form.h1} onChange={(event) => setForm({ ...form, h1: event.target.value })} />
          </Field>
        </div>

        {!isSystem && (
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="URL *">
              <input className={inputClass} placeholder="dostavka или info/sertifikaty" value={form.slug_path} onChange={(event) => setForm({ ...form, slug_path: event.target.value })} />
            </Field>
            <Field label="Шаблон">
              <select className={inputClass} value={form.template} onChange={(event) => setForm({ ...form, template: event.target.value })}>
                {templates.map((template) => (
                  <option key={template.key} value={template.key}>{template.label}</option>
                ))}
              </select>
            </Field>
          </div>
        )}

        {!isSystem && (
          <>
            <Field label="Краткое описание">
              <textarea className={textareaClass} value={form.excerpt} onChange={(event) => setForm({ ...form, excerpt: event.target.value })} />
            </Field>
            <Field label="Контент">
              <textarea className={`${textareaClass} min-h-64 font-mono text-xs leading-relaxed`} value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} />
            </Field>
          </>
        )}

        <div className="rounded-lg border border-[#e4e5da] p-4">
          <h3 className="mb-4 text-lg font-bold text-[#1f3328]">SEO</h3>
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="SEO title">
              <input className={inputClass} value={form.seo_title} onChange={(event) => setForm({ ...form, seo_title: event.target.value })} />
            </Field>
            <Field label="SEO description">
              <textarea className={textareaClass} value={form.seo_description} onChange={(event) => setForm({ ...form, seo_description: event.target.value })} />
            </Field>
            <Field label="OG title">
              <input className={inputClass} value={form.og_title} onChange={(event) => setForm({ ...form, og_title: event.target.value })} />
            </Field>
            <Field label="OG description">
              <textarea className={textareaClass} value={form.og_description} onChange={(event) => setForm({ ...form, og_description: event.target.value })} />
            </Field>
          </div>
          <div className="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
            <Field label="OG изображение">
              <input className={inputClass} value={form.og_image} onChange={(event) => setForm({ ...form, og_image: event.target.value })} />
            </Field>
            <ImageUploader scope="pages" onUploaded={(url) => setForm({ ...form, og_image: url })} />
          </div>
          <div className="mt-4">
            <Field label="Alt OG изображения">
              <input className={inputClass} value={form.og_image_alt} onChange={(event) => setForm({ ...form, og_image_alt: event.target.value })} />
            </Field>
          </div>
          {form.og_image ? (
            <img className="mt-4 h-28 w-48 rounded-md object-cover" src={form.og_image} alt={form.og_image_alt || form.title} />
          ) : null}
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          {!isSystem && (
            <Field label="Дата публикации">
              <input className={inputClass} type="datetime-local" value={form.published_at} onChange={(event) => setForm({ ...form, published_at: event.target.value })} />
            </Field>
          )}
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: event.target.value })} />
          </Field>
        </div>

        <div className="grid gap-3 rounded-lg border border-[#e4e5da] p-4 md:grid-cols-2">
          <Toggle checked={form.is_published} label="Опубликована" onChange={(checked) => setForm({ ...form, is_published: checked })} />
          <Toggle checked={form.is_indexable} label="Индексировать" onChange={(checked) => setForm({ ...form, is_indexable: checked })} />
          <Toggle checked={form.show_in_sitemap} label="Добавлять в sitemap" onChange={(checked) => setForm({ ...form, show_in_sitemap: checked })} />
          <Toggle checked={form.show_in_header} label="Показывать в шапке" onChange={(checked) => setForm({ ...form, show_in_header: checked })} />
          <Toggle checked={form.show_in_footer} label="Показывать в футере" onChange={(checked) => setForm({ ...form, show_in_footer: checked })} />
        </div>
      </form>
    </Modal>
  );
}

function Toggle({ checked, label, onChange }: { checked: boolean; label: string; onChange: (checked: boolean) => void }) {
  return (
    <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
      <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
      {label}
    </label>
  );
}
