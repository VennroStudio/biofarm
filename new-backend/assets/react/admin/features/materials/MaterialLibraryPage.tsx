import { FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { messageFromError } from '../../shared/lib';
import { AdminTable, Badge, Button, Card, ErrorAlert, inputClass, Modal, PageHeader, SearchField, TableCell, TableHead, TableHeaderCell, TableRow } from '../../shared/ui';
import { materialsApi } from './api';
import { MaterialFormModal } from './MaterialFormModal';
import { TargetSearchFilter } from './TargetSearchFilter';
import { PlacementPicker } from './PlacementPicker';
import type { Material, MaterialCategory, MaterialKind, MaterialQuery, Paginated, Placement } from './types';

export function MaterialLibraryPage({ kind }: { kind: MaterialKind }) {
  const [query, setQuery] = useState<MaterialQuery>({ page: 1, per_page: 25 });
  const [result, setResult] = useState<Paginated<Material> | null>(null);
  const [categories, setCategories] = useState<MaterialCategory[]>([]);
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<number | 'new' | null>(null);
  const [initialTab, setInitialTab] = useState<'main' | 'usage'>('main');
  const [selected, setSelected] = useState<number[]>([]);
  const [bulkOpen, setBulkOpen] = useState(false);
  const [placements, setPlacements] = useState<Placement[]>([]);
  const [saving, setSaving] = useState(false);
  const [selectedTarget, setSelectedTarget] = useState<Placement | null>(null);
  useEffect(() => {
    let active = true;
    const timer = window.setTimeout(() => {
      setResult(null); setError(null);
      Promise.all([materialsApi.list(kind, query), kind === 'certificate' ? materialsApi.categories(kind) : Promise.resolve({ items: [] })]).then(([data, categoryData]) => {
        if (!active) return;
        if (data.items.length === 0 && data.total > 0 && (query.page ?? 1) > 1) { setQuery((current) => ({ ...current, page: Math.ceil(data.total / data.per_page) })); return; }
        setResult(data); setCategories(categoryData.items);
      }).catch((e) => { if (active) setError(messageFromError(e, 'Не удалось загрузить список')); });
    }, 200);
    return () => { active = false; window.clearTimeout(timer); };
  }, [kind, query, revision]);
  function filter(next: Partial<MaterialQuery>) { setQuery((current) => ({ ...current, ...next, page: 1 })); setSelected([]); }
  async function remove(item: Material) {
    if (!confirm(`Удалить «${item.title}»?`)) return;
    setError(null);
    try { await materialsApi.delete(kind, item.id); setSelected((current) => current.filter((id) => id !== item.id)); setRevision((current) => current + 1); } catch (e) { setError(messageFromError(e, 'Не удалось удалить запись')); }
  }
  async function attach() {
    setSaving(true); setError(null);
    try { await materialsApi.bulkAttach(kind, selected, placements.map(({ target_type, target_id }) => ({ target_type, target_id }))); setBulkOpen(false); setSelected([]); setRevision((current) => current + 1); } catch (e) { setError(messageFromError(e, 'Не удалось привязать записи')); } finally { setSaving(false); }
  }
  const page = query.page ?? 1;
  const allSelected = Boolean(result?.items.length && result.items.every((item) => selected.includes(item.id)));
  return <>
    <PageHeader title={kind === 'certificate' ? 'Сертификаты' : 'FAQ'} subtitle="Общая библиотека для страниц и товаров" actions={<Button onClick={() => { setInitialTab('main'); setEditing('new'); }}><Plus className="h-4 w-4" />{kind === 'certificate' ? 'Добавить сертификат' : 'Добавить вопрос'}</Button>} />
    {!bulkOpen && <ErrorAlert className="mb-4">{error}</ErrorAlert>}
    <Card className="overflow-hidden p-4 sm:p-6">
      <div className={`mb-4 grid gap-3 ${kind === 'certificate' ? 'lg:grid-cols-4' : 'lg:grid-cols-3'}`} >
        <SearchField value={query.search ?? ''} onChange={(search) => filter({ search })} placeholder="Поиск по названию и содержимому..." />
        {kind === 'certificate' && <select aria-label="Категория" className={inputClass} value={query.category_id ?? ''} onChange={(e) => filter({ category_id: e.target.value })}><option value="">Все категории</option><option value="0">Без категории</option>{categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>}
        <select aria-label="Статус" className={inputClass} value={query.is_active ?? ''} onChange={(e) => filter({ is_active: e.target.value })}><option value="">Все статусы</option><option value="1">Активные</option><option value="0">Неактивные</option></select>
        <select aria-label="Использование" className={inputClass} value={query.usage ?? ''} onChange={(e) => filter({ usage: e.target.value })}><option value="">Любое использование</option><option value="used">Используются</option><option value="unused">Не используются</option></select>
      </div>
      <TargetSearchFilter value={selectedTarget} onChange={(target) => { setSelectedTarget(target); filter({ target_id: target?.target_id ?? '', target_type: target?.target_type ?? '' }); }} />
      <div className="mb-3 flex flex-wrap items-center gap-3"><Badge tone="gray">{result ? `Найдено: ${result.total}` : 'Загрузка...'}</Badge>{selected.length > 0 && <><span className="text-sm">Выбрано: {selected.length}</span><Button size="sm" variant="outline" onClick={() => { setPlacements([]); setError(null); setBulkOpen(true); }}>Привязать выбранные</Button><Button size="sm" variant="ghost" onClick={() => setSelected([])}>Снять выбор</Button></>}</div>
      <div className="overflow-x-auto"><AdminTable><TableHead><tr><TableHeaderCell><input type="checkbox" aria-label="Выбрать все записи на странице" checked={allSelected} disabled={!result?.items.length} onChange={() => setSelected(allSelected ? [] : result?.items.map((item) => item.id) ?? [])} /></TableHeaderCell><TableHeaderCell>{kind === 'certificate' ? 'Название' : 'Вопрос'}</TableHeaderCell>{kind === 'certificate' && <TableHeaderCell>Категория</TableHeaderCell>}<TableHeaderCell>Статус</TableHeaderCell><TableHeaderCell>Использование</TableHeaderCell><TableHeaderCell>Действия</TableHeaderCell></tr></TableHead><tbody>{result?.items.map((item) => <TableRow key={item.id}><TableCell><input type="checkbox" aria-label={`Выбрать «${item.title}»`} checked={selected.includes(item.id)} onChange={() => setSelected((current) => current.includes(item.id) ? current.filter((id) => id !== item.id) : [...current, item.id])} /></TableCell><TableCell><div className="flex items-center gap-3">{kind === 'certificate' && item.file_path && <a href={item.file_path} target="_blank" rel="noreferrer" aria-label={`Открыть документ «${item.title}»`} className="shrink-0">{item.document_type === 'image' ? <img src={item.file_path} alt="" loading="lazy" className="h-12 w-10 rounded border border-[#dfece9] object-cover" /> : <span className="flex h-12 w-10 flex-col items-center justify-center rounded bg-[#eaf5f1] text-[#2e8175]"><FileText className="h-5 w-5" /><span className="text-[10px] font-semibold">PDF</span></span>}</a>}<button className="max-w-md text-left font-semibold text-[#294555] hover:underline" onClick={() => { setInitialTab('main'); setEditing(item.id); }}>{item.title}</button></div></TableCell>{kind === 'certificate' && <TableCell>{item.category_name ?? 'Без категории'}</TableCell>}<TableCell><Badge tone={item.is_active ? 'green' : 'gray'}>{item.is_active ? 'Активен' : 'Неактивен'}</Badge></TableCell><TableCell><button className="text-left text-[#2e8175] hover:underline" onClick={() => { setInitialTab('usage'); setEditing(item.id); }}>{item.usage_count ? `Мест: ${item.usage_count}` : 'Не используется'}</button></TableCell><TableCell><div className="flex gap-1"><Button variant="ghost" size="icon" aria-label={`Редактировать «${item.title}»`} onClick={() => { setInitialTab('main'); setEditing(item.id); }}><Pencil className="h-4 w-4" /></Button><Button variant="ghost" size="icon" aria-label={`Удалить «${item.title}»`} title={item.usage_count ? 'Сначала уберите места использования' : 'Удалить'} disabled={item.usage_count > 0} onClick={() => void remove(item)}><Trash2 className="h-4 w-4" /></Button></div></TableCell></TableRow>)}</tbody></AdminTable></div>
      {result?.items.length === 0 && <p className="p-8 text-center text-sm text-[#5f7580]">По заданным условиям ничего не найдено.</p>}
      <div className="mt-5 flex flex-wrap items-center justify-between gap-3"><select aria-label="Записей на странице" className={`${inputClass} max-w-40`} value={query.per_page} onChange={(e) => filter({ per_page: Number(e.target.value) })}><option value="25">25 на странице</option><option value="50">50 на странице</option></select><div className="flex items-center gap-3"><Button variant="outline" disabled={!result || page <= 1} onClick={() => { setQuery({ ...query, page: page - 1 }); setSelected([]); }}>Назад</Button><span className="text-sm">{page} / {Math.max(1, Math.ceil((result?.total ?? 0) / (query.per_page ?? 25)))}</span><Button variant="outline" disabled={!result || page * result.per_page >= result.total} onClick={() => { setQuery({ ...query, page: page + 1 }); setSelected([]); }}>Далее</Button></div></div>
    </Card>
    <MaterialFormModal kind={kind} initialTab={initialTab} open={editing !== null} id={typeof editing === 'number' ? editing : undefined} onClose={() => setEditing(null)} onSaved={() => { setEditing(null); setRevision((current) => current + 1); }} />
    <Modal open={bulkOpen} title="Привязать выбранные записи" description={`Выбрано записей: ${selected.length}. Существующие места использования сохранятся.`} onClose={() => { if (!saving) setBulkOpen(false); }} footer={<><Button variant="outline" disabled={saving} onClick={() => setBulkOpen(false)}>Отмена</Button><Button disabled={saving || placements.length === 0} onClick={() => void attach()}>{saving ? 'Сохранение...' : 'Привязать'}</Button></>}><ErrorAlert>{error}</ErrorAlert><fieldset disabled={saving}><PlacementPicker value={placements} onChange={setPlacements} /></fieldset></Modal>
  </>;
}
