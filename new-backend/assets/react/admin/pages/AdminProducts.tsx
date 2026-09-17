import { materialsApi } from '../features/materials/api';
import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useRef, useState } from 'react';
import { attributesApi, blogApi, categoriesApi, productGroupsApi, productsApi } from '../api/resources';
import {
  emptyProductForm,
  imageItem,
  productFormFromProduct,
  productPayloadFromForm,
  productPayloadFromProduct,
  type ProductForm,
} from '../features/products/model/productForm';
import { ProductFormModal } from '../features/products/ui/ProductFormModal';
import { ProductTable } from '../features/products/ui/ProductTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { BlogPost, Category, Product, ProductAttribute, ProductGroup } from '../types';

export function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [attributes, setAttributes] = useState<ProductAttribute[]>([]);
  const [productGroups, setProductGroups] = useState<ProductGroup[]>([]);
  const [blogPosts, setBlogPosts] = useState<BlogPost[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<ProductForm>(emptyProductForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [loadingSelection, setLoadingSelection] = useState(false);
  const editRequest = useRef(0);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const categoryById = useMemo(() => new Map(categories.map((category) => [String(category.id), category.name])), [categories]);
  const filteredProducts = useMemo(
    () => products.filter((product) => product.name.toLowerCase().includes(search.toLowerCase())),
    [products, search],
  );

  async function load() {
    const [productResult, categoryResult, attributeResult, productGroupResult, blogResult] = await Promise.all([
      productsApi.list(),
      categoriesApi.list(),
      attributesApi.list(),
      productGroupsApi.list(),
      blogApi.list(),
    ]);
    setProducts(productResult.items);
    setCategories(categoryResult.items);
    setAttributes(attributeResult.items);
    setProductGroups(productGroupResult.items);
    setBlogPosts(blogResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    editRequest.current += 1;
    setLoadingSelection(false);
    setError(null);
    setForm({ ...emptyProductForm, category_id: String(categories[0]?.id ?? '') });
    setDialogOpen(true);
  }

  async function openEdit(product: Product) {
    const requestId = ++editRequest.current;
    setError(null);
    setLoadingSelection(true);
    try {
      const selections = await materialsApi.selections('product', String(product.id));
      if (requestId !== editRequest.current) return;
      setForm({ ...productFormFromProduct(product), ...selections });
      setDialogOpen(true);
    } catch (loadError) {
      if (requestId === editRequest.current) setError(messageFromError(loadError, 'Не удалось загрузить связанные материалы. Повторите открытие.'));
    } finally {
      if (requestId === editRequest.current) setLoadingSelection(false);
    }
  }

  function addImage(url: string) {
    setForm((current) => ({
      ...current,
      image: current.image || url,
      image_items: current.image_items.some((image) => image.path === url)
        ? current.image_items
        : [...current.image_items, imageItem(url, current.image_items.length, current.image_items.length === 0)],
    }));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    try {
      if (form.id) {
        await productsApi.update(form.id, productPayloadFromForm(form));
      } else {
        await productsApi.create(productPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить товар'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(product: Product) {
    if (!confirm(`Удалить товар "${product.name}"?`)) {
      return;
    }
    setError(null);
    try {
      await productsApi.delete(product.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить товар'));
    }
  }

  async function toggleActive(product: Product) {
    setError(null);
    try {
      await productsApi.update(product.id, productPayloadFromProduct(product, { is_active: !product.is_active }));
      await load();
    } catch (toggleError) {
      setError(messageFromError(toggleError, 'Не удалось изменить активность товара'));
    }
  }

  return (
    <>
      <PageHeader
        title="Товары"
        subtitle="Управление каталогом товаров"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить товар</Button>}
      />
      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      {loadingSelection && <p role="status" className="mb-4 text-sm text-[#5f7580]">Загрузка связанных материалов…</p>}
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

      {dialogOpen && <ProductFormModal
        categories={categories}
        attributes={attributes}
        productGroups={productGroups}
        blogPosts={blogPosts}
        form={form}
        open={dialogOpen}
        error={dialogOpen ? error : null}
        saving={saving}
        setForm={setForm}
        onAddImage={addImage}
        onClose={() => {
          setDialogOpen(false);
          setError(null);
        }}
        onSubmit={(event) => void submit(event)}
      />}
    </>
  );
}
