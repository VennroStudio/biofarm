import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { productGroupsApi } from '../api/resources';
import { emptyProductGroupForm, productGroupFormFromGroup, productGroupPayloadFromForm, type ProductGroupForm } from '../features/product-groups/model/productGroupForm';
import { ProductGroupFormModal } from '../features/product-groups/ui/ProductGroupFormModal';
import { ProductGroupsTable } from '../features/product-groups/ui/ProductGroupsTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Button, ErrorAlert, PageHeader } from '../shared/ui';
import type { ProductGroup } from '../types';

export function AdminProductGroups() {
  const [groups, setGroups] = useState<ProductGroup[]>([]);
  const [form, setForm] = useState<ProductGroupForm>(emptyProductGroupForm);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function load() { setGroups((await productGroupsApi.list()).items); }
  useLoadOnMount(load);

  function edit(group?: ProductGroup) {
    setError(null);
    setForm(group ? productGroupFormFromGroup(group) : emptyProductGroupForm);
    setOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    try {
      if (form.id) await productGroupsApi.update(form.id, productGroupPayloadFromForm(form));
      else await productGroupsApi.create(productGroupPayloadFromForm(form));
      setOpen(false);
      await load();
    } catch (e) {
      setError(messageFromError(e, 'Не удалось сохранить группу товаров'));
    } finally { setSaving(false); }
  }

  async function remove(group: ProductGroup) {
    if (!confirm(`Удалить группу "${group.name}"? Товары останутся, но перестанут показываться вариантами друг друга.`)) return;
    setError(null);
    try {
      await productGroupsApi.delete(group.id);
      await load();
    } catch (e) { setError(messageFromError(e, 'Не удалось удалить группу товаров')); }
  }

  return <>
    <PageHeader title="Группы товаров" subtitle="Связанные товары и варианты на странице продукта"
      actions={<Button onClick={() => edit()}><Plus className="h-4 w-4" />Добавить группу</Button>} />
    <ErrorAlert className="mb-5">{open ? null : error}</ErrorAlert>
    <ProductGroupsTable groups={groups} onEdit={edit} onRemove={(group) => void remove(group)} />
    <ProductGroupFormModal form={form} open={open} error={open ? error : null} saving={saving} setForm={setForm}
      onClose={() => { setOpen(false); setError(null); }} onSubmit={(event) => void submit(event)} />
  </>;
}
