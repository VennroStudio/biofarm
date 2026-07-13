import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { Category, ProductAttribute, ProductGroup } from '../../../types';
import { hasProductImage, setMainImage, type ProductForm, type ProductImageForm } from '../model/productForm';

type Props = {
  categories: Category[];
  attributes: ProductAttribute[];
  productGroups: ProductGroup[];
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
  attributes,
  productGroups,
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
  const toggleId = (ids: number[], id: number) => (
    ids.includes(id) ? ids.filter((item) => item !== id) : [...ids, id]
  );
  const updateImage = (index: number, updates: Partial<ProductImageForm>) => {
    const imageItems = form.image_items.map((item, itemIndex) => (
      itemIndex === index ? { ...item, ...updates } : item
    ));
    const mainImage = imageItems.find((item) => item.is_main) ?? imageItems[0];

    setForm({
      ...form,
      image_items: imageItems,
      image: mainImage?.path ?? '',
      image_alt: mainImage?.alt ?? '',
    });
  };
  const removeImage = (index: number) => {
    const imageItems = form.image_items.filter((_, itemIndex) => itemIndex !== index)
      .map((item, itemIndex) => ({ ...item, sort_order: itemIndex }));
    const normalized = imageItems.length > 0 && !imageItems.some((item) => item.is_main)
      ? setMainImage(imageItems, 0)
      : imageItems;
    const mainImage = normalized.find((item) => item.is_main) ?? normalized[0];

    setForm({
      ...form,
      image_items: normalized,
      image: mainImage?.path ?? '',
      image_alt: mainImage?.alt ?? '',
    });
  };
  const markMainImage = (index: number) => {
    const imageItems = setMainImage(form.image_items, index);
    const mainImage = imageItems[index];

    setForm({
      ...form,
      image_items: imageItems,
      image: mainImage?.path ?? '',
      image_alt: mainImage?.alt ?? '',
    });
  };

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
          <Button type="submit" form="admin-product-form" disabled={saving || !form.name || !form.price || !form.weight || !form.category_id || !hasProductImage(form)}>
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

        <Field label="H1">
          <input className={inputClass} value={form.h1} onChange={(event) => setForm({ ...form, h1: event.target.value })} placeholder="Если отличается от названия" />
        </Field>

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

        <div className="grid gap-4 md:grid-cols-3">
          <Field label="SKU">
            <input className={inputClass} value={form.sku} onChange={(event) => setForm({ ...form, sku: event.target.value })} />
          </Field>
          <Field label="GTIN">
            <input className={inputClass} value={form.gtin} onChange={(event) => setForm({ ...form, gtin: event.target.value })} />
          </Field>
          <Field label="Наличие">
            <select className={inputClass} value={form.availability} onChange={(event) => setForm({ ...form, availability: event.target.value })}>
              <option value="in_stock">В наличии</option>
              <option value="out_of_stock">Нет в наличии</option>
              <option value="preorder">Предзаказ</option>
            </select>
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

        {attributes.filter((attribute) => attribute.values.length > 0).map((attribute) => (
          <div key={attribute.id} className="space-y-2">
            <p className="text-sm font-semibold text-[#26382d]">{attribute.name}</p>
            <div className="grid gap-2 sm:grid-cols-2">
              {attribute.values.map((value) => (
                <label key={value.id} className="flex items-center gap-2 rounded-md border border-[#e4e5da] bg-[#fbfaf4] px-3 py-2 text-sm font-semibold text-[#26382d]">
                  <input
                    type="checkbox"
                    checked={form.attribute_value_ids.includes(value.id)}
                    onChange={() => setForm({ ...form, attribute_value_ids: toggleId(form.attribute_value_ids, value.id) })}
                  />
                  {value.name}
                </label>
              ))}
            </div>
          </div>
        ))}

        <div className="rounded-lg border border-[#e4e5da] bg-[#fbfaf4] p-4">
          <Field label="Группа товаров">
            <select className={inputClass} value={form.product_group_id} onChange={(event) => setForm({ ...form, product_group_id: event.target.value })}>
              <option value="">Без группы</option>
              {productGroups.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}
            </select>
          </Field>
        </div>

        <div className="space-y-2">
          <p className="text-sm font-semibold text-[#26382d]">Изображения товара *</p>
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
          {form.image_items.length > 0 && (
            <div className="mt-3 space-y-3">
              {form.image_items.map((image, index) => (
                <div key={`${image.path}-${index}`} className="grid gap-3 rounded-lg border border-[#e4e5da] bg-white p-3 md:grid-cols-[72px_1fr]">
                  <img src={image.path} alt={image.alt} className="h-16 w-16 rounded object-cover" />
                  <div className="grid gap-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                      <input
                        className={inputClass}
                        value={image.path}
                        onChange={(event) => updateImage(index, { path: event.target.value })}
                        placeholder="URL изображения"
                      />
                      <label className="flex items-center gap-2 whitespace-nowrap text-sm font-semibold text-[#26382d]">
                        <input
                          type="radio"
                          name="main-product-image"
                          checked={image.is_main}
                          onChange={() => markMainImage(index)}
                        />
                        Главное
                      </label>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                      <input
                        className={inputClass}
                        value={image.alt}
                        onChange={(event) => updateImage(index, { alt: event.target.value })}
                        placeholder="Alt изображения"
                      />
                      <input
                        className={inputClass}
                        value={image.title}
                        onChange={(event) => updateImage(index, { title: event.target.value })}
                        placeholder="Title изображения"
                      />
                    </div>
                    <div className="flex justify-end">
                      <Button type="button" variant="danger" onClick={() => removeImage(index)}>
                        Удалить фото
                      </Button>
                    </div>
                  </div>
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
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="SEO title">
            <input className={inputClass} value={form.seo_title} onChange={(event) => setForm({ ...form, seo_title: event.target.value })} />
          </Field>
          <Field label="SEO description">
            <textarea className={textareaClass} value={form.seo_description} onChange={(event) => setForm({ ...form, seo_description: event.target.value })} />
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
