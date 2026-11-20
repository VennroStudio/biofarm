import { X } from 'lucide-react';
import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Badge, Button, ErrorAlert, Field, inputClass, Modal } from '../../../shared/ui';
import type { ProductGroupMember } from '../../../types';
import type { ProductGroupForm } from '../model/productGroupForm';
import { ProductGroupSearch } from './ProductGroupSearch';
import { ProductGroupThumbnail } from './ProductGroupThumbnail';

type Props = {
  form: ProductGroupForm;
  open: boolean;
  error?: string | null;
  loading: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<ProductGroupForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function ProductGroupFormModal({ form, open, error, loading, saving, setForm, onClose, onSubmit }: Props) {
  const ready = !loading && (!form.id || Boolean(form.revision));
  const setMain = (id: number | null) => setForm(current => ({ ...current, mainProductId: id }));
  const add = (product: ProductGroupMember) => setForm(current => current.products.some(item => item.id === product.id)
    ? current : { ...current, products: [...current.products, product] });
  const remove = (id: number) => setForm(current => ({
    ...current,
    products: current.products.filter(product => product.id !== id),
    mainProductId: current.mainProductId === id ? null : current.mainProductId,
  }));

  return <Modal open={open} title={form.id ? 'Редактировать группу' : 'Новая группа товаров'} maxWidth="max-w-3xl" onClose={onClose}
    footer={<>
      <Button type="button" variant="outline" disabled={saving} onClick={onClose}>Отмена</Button>
      <Button type="submit" form="admin-product-group-form" disabled={!ready || saving || !form.name.trim()}>
        {saving ? 'Сохранение…' : (form.id ? 'Сохранить' : 'Добавить')}
      </Button>
    </>}>
    <form id="admin-product-group-form" className="space-y-4" onSubmit={onSubmit}>
      <ErrorAlert>{error}</ErrorAlert>
      {loading && <p role="status" className="text-sm text-[#5f7580]">Загружаем товары группы…</p>}
      {ready && <fieldset className="min-w-0 space-y-5" disabled={saving}>
        <Field label="Название *">
          <input className={inputClass} value={form.name} maxLength={255} onChange={event => { const name = event.target.value; setForm(current => ({ ...current, name })); }} />
        </Field>
        <ProductGroupSearch selected={form.products} onAdd={add} />
        <section aria-label="Товары в группе" className="space-y-2">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h3 className="font-semibold text-[#294555]">Товары в группе ({form.products.length})</h3>
            {form.mainProductId !== null && <Button type="button" variant="ghost" size="sm" onClick={() => setMain(null)}>Снять отметку главного</Button>}
          </div>
          {!form.products.length && <p className="rounded-xl border border-dashed border-[#cfe2de] p-4 text-sm text-[#5f7580]">В группе пока нет товаров.</p>}
          <ul className="space-y-2">{form.products.map(product => <li key={product.id} className="flex flex-wrap items-center gap-3 rounded-xl border border-[#dfece9] p-3">
            <ProductGroupThumbnail image={product.image} />
            <div className="min-w-0 flex-1 basis-40">
              <p className="break-words text-sm font-semibold text-[#294555]">{product.name}</p>
              <p className="text-xs text-[#5f7580]">{[product.weight, product.sku, !product.is_active && 'Скрыт на витрине', product.group_name && product.group_id !== form.id && `Перенос из группы «${product.group_name}»`].filter(Boolean).join(' · ')}</p>
            </div>
            <div className="flex items-center gap-1.5">
              {product.id === form.mainProductId ? <Badge>Главный товар</Badge>
                : <Button type="button" variant="outline" size="sm" onClick={() => setMain(product.id)}>Сделать главным</Button>}
              <Button type="button" variant="ghost" size="icon" aria-label={`Убрать из группы: ${product.name}`} onClick={() => remove(product.id)}><X className="h-4 w-4" /></Button>
            </div>
          </li>)}</ul>
        </section>
      </fieldset>}
    </form>
  </Modal>;
}
