import { Edit, Eye, EyeOff, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { categoriesApi, productsApi } from '../api/resources';
import { ImageUploader } from '../components/ImageUploader';
import {
  AdminTable,
  Badge,
  Button,
  Card,
  EmptyState,
  Field,
  inputClass,
  Modal,
  PageHeader,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
  textareaClass,
} from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { Category, Product } from '../types';

type ProductForm = {
  id?: number;
  name: string;
  slug: string;
  category_id: string;
  price: string;
  old_price: string;
  weight: string;
  short_description: string;
  description: string;
  ingredients: string;
  image: string;
  images: string[];
  badge: string;
  features: string;
  wb_link: string;
  ozon_link: string;
  is_active: boolean;
};

const emptyForm: ProductForm = {
  name: '',
  slug: '',
  category_id: '',
  price: '',
  old_price: '',
  weight: '',
  short_description: '',
  description: '',
  ingredients: '',
  image: '',
  images: [],
  badge: '',
  features: '',
  wb_link: '',
  ozon_link: '',
  is_active: true,
};

const money = new Intl.NumberFormat('ru-RU');

function listFromText(value: string): string[] {
  return value.split('\n').map((item) => item.trim()).filter(Boolean);
}

function formFromProduct(product: Product): ProductForm {
  return {
    id: product.id,
    name: product.name,
    slug: product.slug,
    category_id: product.category_id,
    price: String(product.price),
    old_price: product.old_price ? String(product.old_price) : '',
    weight: product.weight,
    short_description: product.short_description ?? '',
    description: product.description,
    ingredients: product.ingredients ?? '',
    image: product.image,
    images: product.images ?? (product.image ? [product.image] : []),
    badge: product.badge ?? '',
    features: (product.features ?? []).join('\n'),
    wb_link: product.wb_link ?? '',
    ozon_link: product.ozon_link ?? '',
    is_active: product.is_active,
  };
}

function payloadFromForm(form: ProductForm) {
  return {
    name: form.name,
    slug: form.slug || null,
    categoryId: form.category_id,
    price: Number(form.price),
    oldPrice: form.old_price ? Number(form.old_price) : null,
    image: form.image || form.images[0] || '',
    images: form.images.length > 0 ? form.images : (form.image ? [form.image] : []),
    badge: form.badge || null,
    weight: form.weight,
    shortDescription: form.short_description || null,
    description: form.description,
    ingredients: form.ingredients || null,
    features: listFromText(form.features),
    wbLink: form.wb_link || null,
    ozonLink: form.ozon_link || null,
    isActive: form.is_active,
  };
}

function payloadFromProduct(product: Product, overrides: Partial<ProductForm> = {}) {
  return payloadFromForm({ ...formFromProduct(product), ...overrides });
}

export function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<ProductForm>(emptyForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [imageUrl, setImageUrl] = useState('');
  const [saving, setSaving] = useState(false);

  const categoryById = useMemo(() => new Map(categories.map((category) => [String(category.id), category.name])), [categories]);
  const filteredProducts = useMemo(
    () => products.filter((product) => product.name.toLowerCase().includes(search.toLowerCase())),
    [products, search],
  );

  async function load() {
    const [productResult, categoryResult] = await Promise.all([productsApi.list(), categoriesApi.list()]);
    setProducts(productResult.items);
    setCategories(categoryResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setForm({ ...emptyForm, category_id: String(categories[0]?.id ?? '') });
    setImageUrl('');
    setDialogOpen(true);
  }

  function openEdit(product: Product) {
    setForm(formFromProduct(product));
    setImageUrl('');
    setDialogOpen(true);
  }

  function addImage(url: string) {
    setForm((current) => ({
      ...current,
      image: current.image || url,
      images: current.images.includes(url) ? current.images : [...current.images, url],
    }));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (form.id) {
        await productsApi.update(form.id, payloadFromForm(form));
      } else {
        await productsApi.create(payloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function remove(product: Product) {
    if (!confirm(`Удалить товар "${product.name}"?`)) {
      return;
    }
    await productsApi.delete(product.id);
    await load();
  }

  async function toggleActive(product: Product) {
    await productsApi.update(product.id, payloadFromProduct(product, { is_active: !product.is_active }));
    await load();
  }

  return (
    <>
      <PageHeader
        title="Товары"
        subtitle="Управление каталогом товаров"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить товар</Button>}
      />

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
            <input
              className={`${inputClass} pl-10`}
              placeholder="Поиск товаров..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <Badge tone="gray">{filteredProducts.length} товаров</Badge>
        </div>

        {filteredProducts.length === 0 ? (
          <EmptyState>Товары не найдены</EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <AdminTable>
              <TableHead>
                <tr>
                  <TableHeaderCell className="w-20">Фото</TableHeaderCell>
                  <TableHeaderCell>Название</TableHeaderCell>
                  <TableHeaderCell>Категория</TableHeaderCell>
                  <TableHeaderCell>Цена</TableHeaderCell>
                  <TableHeaderCell>Маркетплейсы</TableHeaderCell>
                  <TableHeaderCell className="text-right">Действия</TableHeaderCell>
                </tr>
              </TableHead>
              <tbody>
                {filteredProducts.map((product) => (
                  <TableRow key={product.id} className={!product.is_active ? 'opacity-60' : ''}>
                    <TableCell>
                      <img src={product.image} alt={product.name} className="h-12 w-12 rounded object-cover" />
                    </TableCell>
                    <TableCell>
                      <p className="font-semibold text-[#26382d]">{product.name}</p>
                      <p className="text-sm text-[#789083]">{product.weight}</p>
                    </TableCell>
                    <TableCell>
                      <Badge tone="gray">{categoryById.get(product.category_id) || product.category_id}</Badge>
                    </TableCell>
                    <TableCell>
                      <p className="font-semibold">{money.format(product.price)} ₽</p>
                      {product.old_price && <p className="text-sm text-[#789083] line-through">{money.format(product.old_price)} ₽</p>}
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        {product.wb_link && <Badge tone="gray">WB</Badge>}
                        {product.ozon_link && <Badge tone="gray">Ozon</Badge>}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          title={product.is_active ? 'Скрыть товар' : 'Показать товар'}
                          onClick={() => void toggleActive(product)}
                        >
                          {product.is_active ? <Eye className="h-4 w-4 text-[#34a853]" /> : <EyeOff className="h-4 w-4" />}
                        </Button>
                        <Button variant="ghost" size="icon" title="Изменить" onClick={() => openEdit(product)}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" title="Удалить" className="text-[#ef4444]" onClick={() => void remove(product)}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </tbody>
            </AdminTable>
          </div>
        )}
      </Card>

      <Modal
        open={dialogOpen}
        title={form.id ? 'Редактировать товар' : 'Новый товар'}
        description="Заполните информацию о товаре"
        maxWidth="max-w-2xl"
        onClose={() => setDialogOpen(false)}
        footer={(
          <>
            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Отмена</Button>
            <Button type="submit" form="admin-product-form" disabled={saving || !form.name || !form.price || !form.weight || !form.category_id}>
              {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
            </Button>
          </>
        )}
      >
        <form id="admin-product-form" className="grid gap-4" onSubmit={(event) => void submit(event)}>
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
            <textarea className="min-h-48 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 py-2 text-sm outline-none focus:border-[#2f7d4b] focus:bg-white" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} />
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
                  if (imageUrl.trim()) {
                    addImage(imageUrl.trim());
                    setImageUrl('');
                  }
                }}
              >
                Добавить
              </Button>
              <ImageUploader scope="products" onUploaded={addImage} />
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
    </>
  );
}
