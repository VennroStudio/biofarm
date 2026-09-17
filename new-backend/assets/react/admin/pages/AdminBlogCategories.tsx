import { Edit, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { blogCategoriesApi } from '../api/resources';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { AdminTable, Badge, Button, Card, EmptyState, ErrorAlert, Field, inputClass, Modal, PageHeader, SearchField, TableCell, TableHead, TableHeaderCell, TableRow } from '../shared/ui';
import type { BlogCategory } from '../types';

type CategoryForm = { id?: number; name: string; slug: string; sort_order: string };
const emptyForm: CategoryForm = { name: '', slug: '', sort_order: '0' };

export function AdminBlogCategories() {
  const [categories, setCategories] = useState<BlogCategory[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<CategoryForm>(emptyForm);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [deleting, setDeleting] = useState<BlogCategory | null>(null);
  async function load() { setCategories((await blogCategoriesApi.list()).items); }
  useLoadOnMount(load);
  function edit(category?: BlogCategory) {
    setError(null);
    setForm(category ? { ...category, sort_order: String(category.sort_order) } : emptyForm);
    setOpen(true);
  }
  function close() { if (!saving) { setOpen(false); setDeleting(null); setError(null); } }
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true); setError(null);
    try {
      const payload = { name: form.name, slug: form.slug, sort_order: Number(form.sort_order) };
      if (form.id) await blogCategoriesApi.update(form.id, payload);
      else await blogCategoriesApi.create(payload);
      setOpen(false);
      await load();
    } catch (e) { setError(messageFromError(e, 'Не удалось сохранить категорию')); }
    finally { setSaving(false); }
  }
  async function remove() {
    if (!deleting) return;
    setSaving(true); setError(null);
    try { await blogCategoriesApi.delete(deleting.id); setDeleting(null); await load(); }
    catch (e) { setError(messageFromError(e, 'Не удалось удалить категорию')); }
    finally { setSaving(false); }
  }
  const visible = categories.filter(category => `${category.name} ${category.slug}`.toLocaleLowerCase('ru').includes(search.toLocaleLowerCase('ru')));
  return <>
    <PageHeader title="Категории статей" subtitle="Разделы блога и порядок их отображения на сайте" actions={<Button onClick={() => edit()}><Plus className="h-4 w-4" />Добавить категорию</Button>} />
    <ErrorAlert className="mb-5">{open || deleting ? null : error}</ErrorAlert>
    <Card className="p-6">
      <div className="mb-6 flex flex-wrap items-center gap-4"><SearchField placeholder="Поиск категорий…" value={search} onChange={setSearch} /><Badge tone="gray">Всего: {visible.length}</Badge></div>
      {visible.length === 0 ? <EmptyState>Категории не найдены</EmptyState> : <div className="overflow-x-auto"><AdminTable>
        <TableHead><tr><TableHeaderCell>Название</TableHeaderCell><TableHeaderCell>Адрес (slug)</TableHeaderCell><TableHeaderCell>Статей</TableHeaderCell><TableHeaderCell>Порядок</TableHeaderCell><TableHeaderCell className="text-right">Действия</TableHeaderCell></tr></TableHead>
        <tbody>{visible.map(category => <TableRow key={category.id}>
          <TableCell><span className="font-semibold">{category.name}</span></TableCell><TableCell>{category.slug}</TableCell><TableCell><Badge tone="gray">{category.posts_count}</Badge></TableCell><TableCell>{category.sort_order}</TableCell>
          <TableCell><div className="flex justify-end gap-2"><Button variant="ghost" size="icon" title={`Изменить: ${category.name}`} onClick={() => edit(category)}><Edit className="h-4 w-4" /></Button><Button variant="ghost" size="icon" className="text-red-500" title={category.posts_count ? 'Сначала перенесите статьи в другую категорию' : `Удалить: ${category.name}`} disabled={category.posts_count > 0} onClick={() => { setError(null); setDeleting(category); }}><Trash2 className="h-4 w-4" /></Button></div></TableCell>
        </TableRow>)}</tbody>
      </AdminTable></div>}
    </Card>
    <Modal open={open} title={form.id ? 'Редактировать категорию' : 'Новая категория'} onClose={close} footer={<><Button variant="outline" disabled={saving} onClick={close}>Отмена</Button><Button type="submit" form="blog-category-form" disabled={saving}>{saving ? 'Сохранение…' : 'Сохранить'}</Button></>}>
      <form id="blog-category-form" onSubmit={submit} className="space-y-4">
        <ErrorAlert>{error}</ErrorAlert>
        <fieldset disabled={saving} className="space-y-4">
          <Field label="Название *"><input required maxLength={255} className={inputClass} value={form.name} onChange={event => setForm(current => ({ ...current, name: event.target.value }))} placeholder="Например, Здоровье" /></Field>
          <Field label="Адрес (slug)"><input maxLength={255} className={inputClass} value={form.slug} onChange={event => setForm(current => ({ ...current, slug: event.target.value }))} placeholder="Например, health" /></Field>
          <p className="text-xs text-[#5f7580]">Если оставить пустым, сформируется из названия. При изменении привязки статей сохранятся.</p>
          <Field label="Порядок вывода"><input required type="number" min="0" max="2147483647" step="1" className={inputClass} value={form.sort_order} onChange={event => setForm(current => ({ ...current, sort_order: event.target.value }))} /></Field>
          <p className="text-xs text-[#5f7580]">Категории с меньшим числом показываются первыми.</p>
        </fieldset>
      </form>
    </Modal>
    <Modal open={deleting !== null} title="Удалить категорию?" onClose={close} footer={<><Button variant="outline" disabled={saving} onClick={close}>Отмена</Button><Button variant="danger" disabled={saving} onClick={() => void remove()}>{saving ? 'Удаление…' : 'Удалить'}</Button></>}>
      <ErrorAlert>{error}</ErrorAlert><p>Категория «{deleting?.name}» будет убрана из списка. Удаление возможно только для категорий без статей.</p>
    </Modal>
  </>;
}
