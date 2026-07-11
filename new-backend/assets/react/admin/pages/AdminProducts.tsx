import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { categoriesApi, productsApi } from '../api/resources';
import {
  emptyProductForm,
  productFormFromProduct,
  productPayloadFromForm,
  productPayloadFromProduct,
  type ProductForm,
} from '../features/products/model/productForm';
import { ProductFormModal } from '../features/products/ui/ProductFormModal';
import { ProductTable } from '../features/products/ui/ProductTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Badge, Button, Card, PageHeader, SearchField } from '../shared/ui';
import type { Category, Product } from '../types';

export function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<ProductForm>(emptyProductForm);
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
    setForm({ ...emptyProductForm, category_id: String(categories[0]?.id ?? '') });
    setImageUrl('');
    setDialogOpen(true);
  }

  function openEdit(product: Product) {
    setForm(productFormFromProduct(product));
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
        await productsApi.update(form.id, productPayloadFromForm(form));
      } else {
        await productsApi.create(productPayloadFromForm(form));
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
    await productsApi.update(product.id, productPayloadFromProduct(product, { is_active: !product.is_active }));
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
          <SearchField
            className="w-full max-w-sm"
            placeholder="Поиск товаров..."
            value={search}
            onChange={setSearch}
          />
          <Badge tone="gray">{filteredProducts.length} товаров</Badge>
        </div>

        <ProductTable
          categoryById={categoryById}
          products={filteredProducts}
          onEdit={openEdit}
          onRemove={(product) => void remove(product)}
          onToggleActive={(product) => void toggleActive(product)}
        />
      </Card>

      <ProductFormModal
        categories={categories}
        form={form}
        imageUrl={imageUrl}
        open={dialogOpen}
        saving={saving}
        setForm={setForm}
        setImageUrl={setImageUrl}
        onAddImage={addImage}
        onClose={() => setDialogOpen(false)}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}
