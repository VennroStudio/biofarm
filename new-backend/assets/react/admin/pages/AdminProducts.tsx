import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { attributesApi, categoriesApi, productGroupsApi, productsApi } from '../api/resources';
import {
  emptyProductForm,
  imageItem,
  productFormFromProduct,
  productPayloadFromForm,
  productPayloadFromProduct,
  type ProductForm,
} from '../features/products/model/productForm';
import {
  emptyProductGroupForm,
  productGroupFormFromGroup,
  productGroupPayloadFromForm,
  type ProductGroupForm,
} from '../features/product-groups/model/productGroupForm';
import { ProductGroupFormModal } from '../features/product-groups/ui/ProductGroupFormModal';
import { ProductGroupsTable } from '../features/product-groups/ui/ProductGroupsTable';
import { ProductFormModal } from '../features/products/ui/ProductFormModal';
import { ProductTable } from '../features/products/ui/ProductTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Badge, Button, Card, PageHeader, SearchField } from '../shared/ui';
import type { Category, Product, ProductAttribute, ProductGroup } from '../types';

type ProductsTab = 'groups' | 'products';

export function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [attributes, setAttributes] = useState<ProductAttribute[]>([]);
  const [productGroups, setProductGroups] = useState<ProductGroup[]>([]);
  const [activeTab, setActiveTab] = useState<ProductsTab>('products');
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<ProductForm>(emptyProductForm);
  const [groupForm, setGroupForm] = useState<ProductGroupForm>(emptyProductGroupForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [groupDialogOpen, setGroupDialogOpen] = useState(false);
  const [imageUrl, setImageUrl] = useState('');
  const [saving, setSaving] = useState(false);
  const [groupSaving, setGroupSaving] = useState(false);

  const categoryById = useMemo(() => new Map(categories.map((category) => [String(category.id), category.name])), [categories]);
  const filteredProducts = useMemo(
    () => products.filter((product) => product.name.toLowerCase().includes(search.toLowerCase())),
    [products, search],
  );

  async function load() {
    const [productResult, categoryResult, attributeResult, productGroupResult] = await Promise.all([
      productsApi.list(),
      categoriesApi.list(),
      attributesApi.list(),
      productGroupsApi.list(),
    ]);
    setProducts(productResult.items);
    setCategories(categoryResult.items);
    setAttributes(attributeResult.items);
    setProductGroups(productGroupResult.items);
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

  function openCreateGroup() {
    setGroupForm(emptyProductGroupForm);
    setGroupDialogOpen(true);
  }

  function openEditGroup(group: ProductGroup) {
    setGroupForm(productGroupFormFromGroup(group));
    setGroupDialogOpen(true);
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

  async function submitGroup(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setGroupSaving(true);
    try {
      if (groupForm.id) {
        await productGroupsApi.update(groupForm.id, productGroupPayloadFromForm(groupForm));
      } else {
        await productGroupsApi.create(productGroupPayloadFromForm(groupForm));
      }
      setGroupDialogOpen(false);
      await load();
    } finally {
      setGroupSaving(false);
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

  async function removeGroup(group: ProductGroup) {
    if (!confirm(`Удалить группу "${group.name}"? Товары останутся, но перестанут показываться вариантами друг друга.`)) {
      return;
    }
    await productGroupsApi.delete(group.id);
    await load();
  }

  const action = activeTab === 'products'
    ? <Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить товар</Button>
    : <Button onClick={openCreateGroup}><Plus className="h-4 w-4" />Добавить группу</Button>;

  return (
    <>
      <PageHeader
        title="Товары"
        subtitle="Управление каталогом товаров"
        actions={action}
      />

      <div className="mb-5 inline-flex rounded-lg border border-[#e4e5da] bg-white p-1 shadow-sm">
        <button
          type="button"
          className={`rounded-md px-4 py-2 text-sm font-semibold transition ${
            activeTab === 'products' ? 'bg-[#1f6b3a] text-white' : 'text-[#789083] hover:bg-[#eef1e8] hover:text-[#26382d]'
          }`}
          onClick={() => setActiveTab('products')}
        >
          Товары
        </button>
        <button
          type="button"
          className={`rounded-md px-4 py-2 text-sm font-semibold transition ${
            activeTab === 'groups' ? 'bg-[#1f6b3a] text-white' : 'text-[#789083] hover:bg-[#eef1e8] hover:text-[#26382d]'
          }`}
          onClick={() => setActiveTab('groups')}
        >
          Группы
        </button>
      </div>

      {activeTab === 'products' && (
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
      )}

      {activeTab === 'groups' && (
        <ProductGroupsTable
          groups={productGroups}
          onEdit={openEditGroup}
          onRemove={(group) => void removeGroup(group)}
        />
      )}

      <ProductFormModal
        categories={categories}
        attributes={attributes}
        productGroups={productGroups}
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

      <ProductGroupFormModal
        form={groupForm}
        open={groupDialogOpen}
        saving={groupSaving}
        setForm={setGroupForm}
        onClose={() => setGroupDialogOpen(false)}
        onSubmit={(event) => void submitGroup(event)}
      />
    </>
  );
}
