import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { AttributeValueForm } from '../model/attributeForm';

type Props = {
  attributeName: string;
  form: AttributeValueForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<AttributeValueForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function AttributeValueFormModal({ attributeName, form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать значение' : 'Новое значение'}
      description={attributeName}
      maxWidth="max-w-2xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-attribute-value-form" disabled={saving || !form.name}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-attribute-value-form" className="grid gap-4" onSubmit={onSubmit}>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
        </div>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="H1">
            <input className={inputClass} value={form.h1} onChange={(event) => setForm({ ...form, h1: event.target.value })} />
          </Field>
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: event.target.value })} />
          </Field>
        </div>
        <Field label="Короткое описание">
          <textarea className={textareaClass} value={form.short_description} onChange={(event) => setForm({ ...form, short_description: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="SEO title">
            <input className={inputClass} value={form.seo_title} onChange={(event) => setForm({ ...form, seo_title: event.target.value })} />
          </Field>
          <Field label="SEO description">
            <textarea className={textareaClass} value={form.seo_description} onChange={(event) => setForm({ ...form, seo_description: event.target.value })} />
          </Field>
        </div>
        <Field label="Синонимы, по одному в строке">
          <textarea className={textareaClass} value={form.synonyms} onChange={(event) => setForm({ ...form, synonyms: event.target.value })} />
        </Field>
        <Field label="Вступительный текст">
          <textarea className={textareaClass} value={form.intro_text} onChange={(event) => setForm({ ...form, intro_text: event.target.value })} />
        </Field>
        <Field label="Нижний SEO-текст">
          <textarea className={textareaClass} value={form.bottom_text} onChange={(event) => setForm({ ...form, bottom_text: event.target.value })} />
        </Field>
        <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
          <input type="checkbox" checked={form.is_indexable} onChange={(event) => setForm({ ...form, is_indexable: event.target.checked })} />
          Индексировать значение
        </label>
      </form>
    </Modal>
  );
}
