import { Edit, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { categoriesApi, productsApi } from '../api/resources';
import { Badge, Button, Card, EmptyState, Field, inputClass, Modal, PageHeader } from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { Category, Product } from '../types';

type CategoryForm = {
  id?: number;
  name: string;
  slug: string;
};

const emptyForm: CategoryForm = {
  name: '',
  slug: '',
};

export function AdminCategories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [form, setForm] = useState<CategoryForm>(emptyForm);
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
    setForm(emptyForm);
    setDialogOpen(true);
  }

  function openEdit(category: Category) {
    setForm({ id: category.id, name: category.name, slug: category.slug });
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const payload = { name: form.name, slug: form.slug || null };
      if (form.id) {
        await categoriesApi.update(form.id, payload);
      } else {
        await categoriesApi.create(payload);
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

      {categories.length === 0 ? (
        <EmptyState>Категории не найдены</EmptyState>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {categories.map((category) => (
            <Card key={category.id} className="group p-6">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <h3 className="text-lg font-semibold text-[#26382d]">{category.name}</h3>
                  <Badge tone="gray" className="mt-3">{productCounts.get(String(category.id)) ?? 0} товаров</Badge>
                </div>
                <div className="flex gap-1 opacity-0 transition group-hover:opacity-100">
                  <Button variant="ghost" size="icon" onClick={() => openEdit(category)} title="Изменить">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => void remove(category)} title="Удалить">
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </Card>
          ))}
        </div>
      )}

      <Modal
        open={dialogOpen}
        title={form.id ? 'Редактировать категорию' : 'Новая категория'}
        onClose={() => setDialogOpen(false)}
        maxWidth="max-w-md"
        footer={(
          <>
            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Отмена</Button>
            <Button type="submit" form="admin-category-form" disabled={saving || !form.name}>
              {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
            </Button>
          </>
        )}
      >
        <form id="admin-category-form" className="grid gap-4" onSubmit={(event) => void submit(event)}>
          <Field label="Название *">
            <input className={inputClass} value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Slug">
            <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
          </Field>
        </form>
      </Modal>
    </>
  );
}
