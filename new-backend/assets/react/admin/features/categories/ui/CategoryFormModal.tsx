import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Field, inputClass, Modal } from '../../../shared/ui';
import type { CategoryForm } from '../model/categoryForm';

type Props = {
  form: CategoryForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<CategoryForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function CategoryFormModal({ form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать категорию' : 'Новая категория'}
      onClose={onClose}
      maxWidth="max-w-md"
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
        <Field label="Название *">
          <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
        </Field>
        <Field label="Slug">
          <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
        </Field>
      </form>
    </Modal>
  );
}
