import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { Category } from '../../../types';
import type { CategoryForm } from '../model/categoryForm';

type Props = {
  categories: Category[];
  form: CategoryForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<CategoryForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function CategoryFormModal({ categories, form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать категорию' : 'Новая категория'}
      onClose={onClose}
      maxWidth="max-w-2xl"
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-category-form" disabled={saving || !form.name}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-category-form" className="grid gap-4" onSubmit={onSubmit}>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
        </div>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Родительская категория">
            <select className={inputClass} value={form.parent_id} onChange={(event) => setForm({ ...form, parent_id: event.target.value })}>
              <option value="">Без родителя</option>
              {categories
                .filter((category) => category.id !== form.id)
                .map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
            </select>
          </Field>
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: event.target.value })} />
          </Field>
        </div>
        <Field label="H1">
          <input className={inputClass} value={form.h1} onChange={(event) => setForm({ ...form, h1: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="SEO title">
            <input className={inputClass} value={form.seo_title} onChange={(event) => setForm({ ...form, seo_title: event.target.value })} />
          </Field>
          <Field label="SEO description">
            <textarea className={textareaClass} value={form.seo_description} onChange={(event) => setForm({ ...form, seo_description: event.target.value })} />
          </Field>
        </div>
        <Field label="Изображение">
          <input className={inputClass} value={form.image} onChange={(event) => setForm({ ...form, image: event.target.value })} />
        </Field>
        <Field label="Вступительный текст">
          <textarea className={textareaClass} value={form.intro_text} onChange={(event) => setForm({ ...form, intro_text: event.target.value })} />
        </Field>
        <Field label="Нижний SEO-текст">
          <textarea className={textareaClass} value={form.bottom_text} onChange={(event) => setForm({ ...form, bottom_text: event.target.value })} />
        </Field>
        <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
          <input type="checkbox" checked={form.is_indexable} onChange={(event) => setForm({ ...form, is_indexable: event.target.checked })} />
          Индексировать
        </label>
      </form>
    </Modal>
  );
}
