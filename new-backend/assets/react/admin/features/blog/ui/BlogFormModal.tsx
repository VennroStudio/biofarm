import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { BlogForm } from '../model/blogForm';

type Props = {
  form: BlogForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<BlogForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function BlogFormModal({ form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать статью' : 'Новая статья'}
      maxWidth="max-w-3xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-blog-form" disabled={saving || !form.title || !form.excerpt || !form.content}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Опубликовать')}
          </Button>
        </>
      )}
    >
      <form id="admin-blog-form" className="grid gap-4" onSubmit={onSubmit}>
        <Field label="Заголовок *">
          <input className={inputClass} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Категория *">
            <input className={inputClass} value={form.category_id} onChange={(event) => setForm({ ...form, category_id: event.target.value })} placeholder="health" />
          </Field>
          <Field label="Имя автора">
            <input className={inputClass} value={form.author_name} onChange={(event) => setForm({ ...form, author_name: event.target.value })} />
          </Field>
        </div>
        <Field label="URL изображения">
          <input className={inputClass} value={form.image} onChange={(event) => setForm({ ...form, image: event.target.value })} />
        </Field>
        <ImageUploader scope="blog" onUploaded={(url) => setForm({ ...form, image: url })} />
        <Field label="Краткое описание *">
          <textarea className={textareaClass} value={form.excerpt} onChange={(event) => setForm({ ...form, excerpt: event.target.value })} />
        </Field>
        <Field label="Содержание *">
          <textarea className={`${textareaClass} min-h-72`} value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
          <Field label="Минут чтения">
            <input className={inputClass} type="number" value={form.read_time} onChange={(event) => setForm({ ...form, read_time: event.target.value })} />
          </Field>
        </div>
        <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
          <input type="checkbox" checked={form.is_published} onChange={(event) => setForm({ ...form, is_published: event.target.checked })} />
          Опубликована
        </label>
      </form>
    </Modal>
  );
}
