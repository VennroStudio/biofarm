import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { messageFromError } from '../../shared/lib';
import { AdminTable, Button, Card, ErrorAlert, Field, inputClass, Modal, PageHeader, TableCell, TableHead, TableHeaderCell, TableRow } from '../../shared/ui';
import { materialsApi } from './api';
import type { MaterialCategory, MaterialKind } from './types';
export function MaterialCategoriesPage({ kind }: { kind: MaterialKind }) {
  const [items, setItems] = useState<MaterialCategory[]>([]);
  const [revision, setRevision] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<MaterialCategory | 'new' | null>(null);
  const [name, setName] = useState('');
  const [saving, setSaving] = useState(false);
  useEffect(() => {
    let active = true;
    materialsApi.categories(kind).then((data) => { if (active) { setItems(data.items); setLoading(false); } }).catch((e) => { if (active) { setError(messageFromError(e, 'Не удалось загрузить категории')); setLoading(false); } });
    return () => { active = false; };
  }, [kind, revision]);
  function edit(item: MaterialCategory | 'new') { setName(item === 'new' ? '' : item.name); setError(null); setEditing(item); }
  async function submit(event: FormEvent) {
    event.preventDefault(); if (!editing || saving) return;
    setSaving(true); setError(null);
    try { if (editing === 'new') await materialsApi.createCategory(kind, name.trim()); else await materialsApi.updateCategory(kind, editing.id, name.trim()); setEditing(null); setRevision((current) => current + 1); } catch (e) { setError(messageFromError(e, 'Не удалось сохранить категорию')); } finally { setSaving(false); }
  }
  async function remove(item: MaterialCategory) {
    if (!confirm(`Удалить категорию «${item.name}»?`)) return;
    setError(null);
    try { await materialsApi.deleteCategory(kind, item.id); setRevision((current) => current + 1); } catch (e) { setError(messageFromError(e, 'Не удалось удалить категорию')); }
  }
  return <><PageHeader title="Категории" subtitle={kind === 'certificate' ? 'Категории сертификатов' : 'Категории FAQ'} actions={<Button onClick={() => edit('new')}><Plus className="h-4 w-4" />Добавить категорию</Button>} />{!editing && <ErrorAlert className="mb-4">{error}</ErrorAlert>}<Card className="overflow-x-auto p-4"><AdminTable><TableHead><tr><TableHeaderCell>Название</TableHeaderCell><TableHeaderCell>Записей</TableHeaderCell><TableHeaderCell>Действия</TableHeaderCell></tr></TableHead><tbody>{items.map((item) => <TableRow key={item.id}><TableCell>{item.name}</TableCell><TableCell>{item.usage_count}</TableCell><TableCell><div className="flex gap-2"><Button variant="ghost" size="icon" aria-label={`Редактировать «${item.name}»`} onClick={() => edit(item)}><Pencil className="h-4 w-4" /></Button><Button variant="ghost" size="icon" aria-label={`Удалить «${item.name}»`} disabled={item.usage_count > 0} title={item.usage_count ? 'Сначала перенесите записи в другую категорию' : 'Удалить'} onClick={() => void remove(item)}><Trash2 className="h-4 w-4" /></Button></div></TableCell></TableRow>)}</tbody></AdminTable>{loading ? <p className="p-6 text-sm">Загрузка...</p> : !items.length && <p className="p-6 text-sm text-[#5f7580]">Категории пока не созданы.</p>}<p className="mt-4 text-xs text-[#5f7580]">Удалить можно только пустую категорию.</p></Card><Modal open={editing !== null} title={editing === 'new' ? 'Новая категория' : 'Редактировать категорию'} onClose={() => { if (!saving) setEditing(null); }} footer={<><Button variant="outline" disabled={saving} onClick={() => setEditing(null)}>Отмена</Button><Button type="submit" form="material-category-form" disabled={saving || !name.trim()}>{saving ? 'Сохранение...' : 'Сохранить'}</Button></>}><form id="material-category-form" onSubmit={(event) => void submit(event)}><ErrorAlert>{error}</ErrorAlert><Field label="Название *"><input className={inputClass} value={name} required maxLength={255} disabled={saving} onChange={(e) => setName(e.target.value)} /></Field></form></Modal></>;
}
