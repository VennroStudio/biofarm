import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { categoriesApi, productsApi } from '../api/resources';
import {
  categoryFormFromCategory,
  categoryPayloadFromForm,
  emptyCategoryForm,
  type CategoryForm,
} from '../features/categories/model/categoryForm';
import { CategoryCards } from '../features/categories/ui/CategoryCards';
import { CategoryFormModal } from '../features/categories/ui/CategoryFormModal';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Button, PageHeader } from '../shared/ui';
import type { Category, Product } from '../types';

export function AdminCategories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [form, setForm] = useState<CategoryForm>(emptyCategoryForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [saving, setSaving] = useState(false);

  const productCounts = useMemo(() => {
    const counts = new Map<string, number>();
    products.forEach((product) => counts.set(product.category_id, (counts.get(product.category_id) ?? 0) + 1));
    return counts;
  }, [products]);

  async function load() {
    const [categoryResult, productResult] = await Promise.all([categoriesApi.list(), productsApi.list()]);
    setCategories(categoryResult.items);
    setProducts(productResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setForm(emptyCategoryForm);
    setDialogOpen(true);
  }

  function openEdit(category: Category) {
    setForm(categoryFormFromCategory(category));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (form.id) {
        await categoriesApi.update(form.id, categoryPayloadFromForm(form));
      } else {
        await categoriesApi.create(categoryPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function remove(category: Category) {
    const count = productCounts.get(String(category.id)) ?? 0;
    if (count > 0) {
      alert('Нельзя удалить категорию, в которой есть товары.');
      return;
    }
    if (!confirm(`Удалить категорию "${category.name}"?`)) {
      return;
    }
    await categoriesApi.delete(category.id);
    await load();
  }

  return (
    <>
      <PageHeader
        title="Категории"
        subtitle="Управление категориями товаров"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить категорию</Button>}
      />

      <CategoryCards
        categories={categories}
        productCounts={productCounts}
        onEdit={openEdit}
        onRemove={(category) => void remove(category)}
      />

      <CategoryFormModal
        form={form}
        open={dialogOpen}
        saving={saving}
        setForm={setForm}
        onClose={() => setDialogOpen(false)}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}
