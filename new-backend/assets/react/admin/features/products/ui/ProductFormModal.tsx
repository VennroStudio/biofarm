import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { Category } from '../../../types';
import type { ProductForm } from '../model/productForm';

type Props = {
  categories: Category[];
  form: ProductForm;
  imageUrl: string;
  open: boolean;
  saving: boolean;
  setForm: Dispatch<SetStateAction<ProductForm>>;
  setImageUrl: Dispatch<SetStateAction<string>>;
  onAddImage: (url: string) => void;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function ProductFormModal({
  categories,
  form,
  imageUrl,
  open,
  saving,
  setForm,
  setImageUrl,
  onAddImage,
  onClose,
  onSubmit,
}: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать товар' : 'Новый товар'}
      description="Заполните информацию о товаре"
      maxWidth="max-w-2xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-product-form" disabled={saving || !form.name || !form.price || !form.weight || !form.category_id}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-product-form" className="grid gap-4" onSubmit={onSubmit}>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Категория *">
            <select className={inputClass} value={form.category_id} onChange={(event) => setForm({ ...form, category_id: event.target.value })}>
              <option value="" disabled>Выберите категорию</option>
              {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
            </select>
          </Field>
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <Field label="Цена *">
            <input className={inputClass} type="number" value={form.price} onChange={(event) => setForm({ ...form, price: event.target.value })} />
          </Field>
          <Field label="Старая цена">
            <input className={inputClass} type="number" value={form.old_price} onChange={(event) => setForm({ ...form, old_price: event.target.value })} />
          </Field>
          <Field label="Вес/Объём *">
            <input className={inputClass} value={form.weight} onChange={(event) => setForm({ ...form, weight: event.target.value })} placeholder="130 гр" />
          </Field>
        </div>

        <Field label="Краткое описание">
          <textarea className={textareaClass} value={form.short_description} onChange={(event) => setForm({ ...form, short_description: event.target.value })} />
        </Field>
        <Field label="Полное описание *">
          <textarea className={`${textareaClass} min-h-48`} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} />
        </Field>
        <Field label="Состав">
          <input className={inputClass} value={form.ingredients} onChange={(event) => setForm({ ...form, ingredients: event.target.value })} />
        </Field>
        <Field label="URL основного изображения">
          <input className={inputClass} value={form.image} onChange={(event) => setForm({ ...form, image: event.target.value })} />
        </Field>

        <div className="space-y-2">
          <p className="text-sm font-semibold text-[#26382d]">Дополнительные фото</p>
          <div className="flex flex-wrap gap-2">
            <input className={`${inputClass} flex-1`} value={imageUrl} onChange={(event) => setImageUrl(event.target.value)} placeholder="URL изображения" />
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                const url = imageUrl.trim();
                if (url) {
                  onAddImage(url);
                  setImageUrl('');
                }
              }}
            >
              Добавить
            </Button>
            <ImageUploader scope="products" onUploaded={onAddImage} />
          </div>
          {form.images.length > 0 && (
            <div className="mt-2 flex flex-wrap gap-2">
              {form.images.map((image, index) => (
                <div key={`${image}-${index}`} className="group relative">
                  <img src={image} alt="" className="h-16 w-16 rounded object-cover" />
                  <button
                    type="button"
                    className="absolute -right-2 -top-2 grid h-5 w-5 place-items-center rounded-full bg-[#b94b4b] text-xs text-white opacity-0 transition group-hover:opacity-100"
                    onClick={() => setForm({ ...form, images: form.images.filter((_, itemIndex) => itemIndex !== index) })}
                  >
                    ×
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Бейдж">
            <input className={inputClass} value={form.badge} onChange={(event) => setForm({ ...form, badge: event.target.value })} placeholder="Хит" />
          </Field>
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
        </div>
        <Field label="Особенности, по одной в строке">
          <textarea className={textareaClass} value={form.features} onChange={(event) => setForm({ ...form, features: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Ссылка Wildberries">
            <input className={inputClass} value={form.wb_link} onChange={(event) => setForm({ ...form, wb_link: event.target.value })} />
          </Field>
          <Field label="Ссылка Ozon">
            <input className={inputClass} value={form.ozon_link} onChange={(event) => setForm({ ...form, ozon_link: event.target.value })} />
          </Field>
        </div>
        <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
          <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />
          Активен
        </label>
      </form>
    </Modal>
  );
}
