import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Field, inputClass, Modal } from '../../../shared/ui';
import type { AttributeForm } from '../model/attributeForm';

type Props = {
  form: AttributeForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<AttributeForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function AttributeFormModal({ form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать атрибут' : 'Новый атрибут'}
      description="Например: Состав, Для чего, Объём, Форма выпуска"
      maxWidth="max-w-xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-attribute-form" disabled={saving || !form.name}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-attribute-form" className="grid gap-4" onSubmit={onSubmit}>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
        </div>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Префикс фильтра">
            <input className={inputClass} value={form.filter_prefix} onChange={(event) => setForm({ ...form, filter_prefix: event.target.value })} placeholder="sostav" />
          </Field>
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: event.target.value })} />
          </Field>
        </div>
        <div className="grid gap-2 text-sm font-semibold text-[#26382d]">
          <label className="flex items-center gap-2">
            <input type="checkbox" checked={form.is_filterable} onChange={(event) => setForm({ ...form, is_filterable: event.target.checked })} />
            Использовать как фильтр
          </label>
          <label className="flex items-center gap-2">
            <input type="checkbox" checked={form.is_indexable} onChange={(event) => setForm({ ...form, is_indexable: event.target.checked })} />
            Разрешить SEO-страницы значений
          </label>
          <label className="flex items-center gap-2">
            <input type="checkbox" checked={form.show_on_product} onChange={(event) => setForm({ ...form, show_on_product: event.target.checked })} />
            Показывать в карточке товара
          </label>
        </div>
      </form>
    </Modal>
  );
}
