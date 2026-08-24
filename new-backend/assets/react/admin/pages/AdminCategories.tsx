import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { categoriesApi, productsApi } from '../api/resources';
import {
  categoryFormFromCategory,
  categoryPayloadFromForm,
  emptyCategoryForm,
  type CategoryForm,
} from '../features/categories/model/categoryForm';
import { CategoryFormModal } from '../features/categories/ui/CategoryFormModal';
import { CategoryList } from '../features/categories/ui/CategoryList';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Button, ErrorAlert, PageHeader } from '../shared/ui';
import type { Category, Product } from '../types';

export function AdminCategories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [form, setForm] = useState<CategoryForm>(emptyCategoryForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const productCounts = useMemo(() => {
    const counts = new Map<string, number>();
    products.forEach((product) => counts.set(product.category_id, (counts.get(product.category_id) ?? 0) + 1));
    return counts;
  }, [products]);

  const childCounts = useMemo(() => {
    const counts = new Map<string, number>();
    categories.forEach((category) => {
      if (category.parent_id) {
        const parentId = String(category.parent_id);
        counts.set(parentId, (counts.get(parentId) ?? 0) + 1);
      }
    });
    return counts;
  }, [categories]);

  async function load() {
    const [categoryResult, productResult] = await Promise.all([categoriesApi.list(), productsApi.list()]);
    setCategories(categoryResult.items);
    setProducts(productResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyCategoryForm);
    setDialogOpen(true);
  }

  function openEdit(category: Category) {
    setError(null);
    setForm(categoryFormFromCategory(category));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await categoriesApi.update(form.id, categoryPayloadFromForm(form));
      } else {
        await categoriesApi.create(categoryPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить категорию'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(category: Category) {
    const count = productCounts.get(String(category.id)) ?? 0;
    const children = childCounts.get(String(category.id)) ?? 0;
    if (children > 0) {
      setError('Нельзя удалить категорию, у которой есть подкатегории.');
      return;
    }
    if (count > 0) {
      setError('Нельзя удалить категорию, в которой есть товары.');
      return;
    }
    if (!confirm(`Удалить категорию "${category.name}"?`)) {
      return;
    }
    setError(null);
    try {
      await categoriesApi.delete(category.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить категорию'));
    }
  }

  return (
    <>
      <PageHeader
        title="Категории"
        subtitle="Управление категориями товаров"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить категорию</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <CategoryList
        categories={categories}
        productCounts={productCounts}
        onEdit={openEdit}
        onRemove={(category) => void remove(category)}
      />

      <CategoryFormModal
        categories={categories}
        error={dialogOpen ? error : null}
        form={form}
        open={dialogOpen}
        saving={saving}
        setForm={setForm}
        onClose={() => {
          setDialogOpen(false);
          setError(null);
        }}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}
