import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
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
        <div className="space-y-2">
          <p className="text-sm font-semibold text-[#26382d]">Изображение</p>
          <ImageUploader scope="categories" onUploaded={(url) => setForm({ ...form, image: url })} />
          {form.image ? (
            <div className="grid gap-3 rounded-lg border border-[#e4e5da] bg-white p-3 md:grid-cols-[88px_1fr]">
              <img src={form.image} alt={form.name || 'Изображение категории'} className="h-20 w-20 rounded object-cover" />
              <div className="grid gap-2">
                <p className="break-all rounded-md border border-[#e4e5da] bg-[#fbfaf4] px-3 py-2 text-xs font-semibold text-[#789083]">
                  {form.image}
                </p>
                <div className="flex justify-end">
                  <Button type="button" variant="danger" onClick={() => setForm({ ...form, image: '' })}>
                    Удалить изображение
                  </Button>
                </div>
              </div>
            </div>
          ) : null}
        </div>
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
