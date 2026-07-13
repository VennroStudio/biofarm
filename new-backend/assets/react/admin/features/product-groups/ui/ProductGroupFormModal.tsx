import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Field, inputClass, Modal } from '../../../shared/ui';
import type { ProductGroupForm } from '../model/productGroupForm';

type Props = {
  form: ProductGroupForm;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<ProductGroupForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function ProductGroupFormModal({ form, open, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать группу' : 'Новая группа товаров'}
      description="Группа связывает несколько товаров, чтобы показать их карточками на странице товара"
      maxWidth="max-w-xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-product-group-form" disabled={saving || !form.name}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-product-group-form" className="grid gap-4" onSubmit={onSubmit}>
        <Field label="Название *">
          <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} placeholder="Группа 1" />
        </Field>
      </form>
    </Modal>
  );
}
