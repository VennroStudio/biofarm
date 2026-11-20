import { Plus } from 'lucide-react';
import { useRef, useState, type FormEvent } from 'react';
import { productGroupsApi } from '../api/resources';
import { emptyProductGroupForm, productGroupFormFromGroup, productGroupPayloadFromForm, type ProductGroupForm } from '../features/product-groups/model/productGroupForm';
import { ProductGroupFormModal } from '../features/product-groups/ui/ProductGroupFormModal';
import { ProductGroupsTable } from '../features/product-groups/ui/ProductGroupsTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { ProductGroup } from '../types';

export function AdminProductGroups() {
  const [groups, setGroups] = useState<ProductGroup[]>([]);
  const [form, setForm] = useState<ProductGroupForm>(emptyProductGroupForm);
  const [search, setSearch] = useState('');
  const [loadingGroup, setLoadingGroup] = useState(false);
  const detailsRequest = useRef(0);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function load() { setGroups((await productGroupsApi.list()).items); }
  useLoadOnMount(load);

  async function edit(group?: ProductGroup) {
    const request = ++detailsRequest.current;
    setError(null);
    setForm(group ? { ...emptyProductGroupForm, id: group.id, name: group.name } : emptyProductGroupForm);
    setLoadingGroup(Boolean(group));
    setOpen(true);
    if (!group) return;
    try {
      const details = await productGroupsApi.details(group.id);
      if (detailsRequest.current === request) setForm(productGroupFormFromGroup(details));
    } catch (reason) {
      if (detailsRequest.current === request) setError(messageFromError(reason, 'Не удалось загрузить товары группы'));
    } finally {
      if (detailsRequest.current === request) setLoadingGroup(false);
    }
  }

  function close() {
    if (saving) return;
    detailsRequest.current += 1;
    setOpen(false);
    setError(null);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (loadingGroup || saving || (form.id && !form.revision)) return;
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

  const query = search.trim().toLocaleLowerCase('ru');
  const filtered = groups.filter(group => group.name.toLocaleLowerCase('ru').includes(query));

  return <>
    <PageHeader title="Группы товаров" subtitle="Связанные товары и варианты на странице продукта"
      actions={<Button onClick={() => void edit()}><Plus className="h-4 w-4" />Добавить группу</Button>} />
    <ErrorAlert className="mb-5">{open ? null : error}</ErrorAlert>
    <SearchField className="mb-5 w-full max-w-sm" value={search} onChange={setSearch} placeholder="Поиск группы по названию" />
    {query && !filtered.length ? <Card className="p-8 text-center text-[#5f7580]">Группы не найдены</Card>
      : <ProductGroupsTable groups={filtered} onEdit={group => void edit(group)} onRemove={group => void remove(group)} />}
    {open && <ProductGroupFormModal form={form} open error={error} loading={loadingGroup} saving={saving} setForm={setForm}
      onClose={close} onSubmit={event => void submit(event)} />}
  </>;
}
