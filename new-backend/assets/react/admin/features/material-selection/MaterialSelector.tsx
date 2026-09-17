import { useEffect, useState } from 'react';
import { ArrowDown, ArrowUp, FileText, GripVertical, Plus, X } from 'lucide-react';
import { materialsApi } from '../materials/api';
import type { Material, MaterialCategory, MaterialKind } from '../materials/types';
import { MaterialFormModal } from '../materials/MaterialFormModal';
import { messageFromError } from '../../shared/lib';
import { Button, ErrorAlert, Field, inputClass, Modal } from '../../shared/ui';

type Props = { kind: MaterialKind; ids: number[]; onChange: (ids: number[]) => void };
const toggle = (ids: number[], id: number) => ids.includes(id) ? ids.filter(value => value !== id) : [...ids, id];

export function MaterialSelector({ kind, ids, onChange }: Props) {
  const [items, setItems] = useState<Record<number, Material>>({});
  const [error, setError] = useState<string | null>(null);
  const [retry, setRetry] = useState(0);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [draggedId, setDraggedId] = useState<number | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const selectedKey = [...ids].sort((left, right) => left - right).join(',');
  const title = kind === 'certificate' ? 'Сертификаты' : 'Вопросы и ответы';
  useEffect(() => {
    if (!selectedKey) return;
    let cancelled = false;
    const selectedIds = selectedKey.split(',');
    const batches = Array.from({ length: Math.ceil(selectedIds.length / 50) }, (_, index) => selectedIds.slice(index * 50, (index + 1) * 50).join(','));
    Promise.all(batches.map(batch => materialsApi.list(kind, { ids: batch, per_page: 50 }))).then(results => {
      if (cancelled) return;
      const records = results.flatMap(result => result.items);
      setItems(current => ({ ...current, ...Object.fromEntries(records.map(item => [item.id, item])) }));
      setError(records.length === selectedIds.length ? null : 'Некоторые выбранные материалы недоступны. Проверьте список перед сохранением.');
    }).catch(reason => { if (!cancelled) setError(messageFromError(reason, 'Не удалось загрузить выбранные материалы')); });
    return () => { cancelled = true; };
  }, [kind, selectedKey, retry]);

  function move(index: number, direction: number) {
    const next = [...ids];
    [next[index], next[index + direction]] = [next[index + direction], next[index]];
    onChange(next);
  }
  function created(item: Material) {
    setItems(current => ({ ...current, [item.id]: item }));
    onChange(ids.includes(item.id) ? ids : [...ids, item.id]);
    setCreateOpen(false);
  }

  return <section className="space-y-4 rounded-2xl border border-[#dfece9] bg-white p-4 sm:p-5">
    <div className="flex flex-wrap items-center justify-between gap-3"><h3 className="text-base font-semibold text-[#294555]">{title} <span className="text-sm text-[#5f7580]">({ids.length})</span></h3><div className="flex flex-wrap gap-2"><Button variant="outline" size="sm" onClick={() => setPickerOpen(true)}>Добавить существующие</Button><Button variant="secondary" size="sm" onClick={() => setCreateOpen(true)}><Plus className="h-4 w-4" />Создать</Button></div></div>
    <p className="text-xs text-[#5f7580]">Порядок и выбор сохраняются вместе с основной формой. Созданный материал сразу появится в общей библиотеке.</p>
    <ErrorAlert>{error}</ErrorAlert>{error && <Button size="sm" variant="outline" onClick={() => setRetry(value => value + 1)}>Повторить загрузку</Button>}
    {!ids.length && <p className="rounded-xl border border-dashed border-[#cfe2de] p-5 text-center text-sm text-[#5f7580]">Материалы ещё не выбраны.</p>}
    <ol className="space-y-2">{ids.map((id, index) => {
      const item = items[id];
      const name = item?.title || item?.question || `Материал #${id}`;
      return <li key={id} className={`flex flex-wrap items-center gap-3 rounded-xl border border-[#dfece9] p-3 ${draggedId === id ? 'opacity-50' : ''}`}
        onDragOver={event => { if (draggedId !== null) event.preventDefault(); }}
        onDrop={event => {
          if (draggedId === null) return;
          event.preventDefault();
          const sourceIndex = ids.indexOf(draggedId);
          if (sourceIndex >= 0 && sourceIndex !== index) {
            const next = ids.filter(value => value !== draggedId);
            next.splice(index, 0, draggedId);
            onChange(next);
          }
          setDraggedId(null);
        }}>
        <button type="button" draggable aria-label={`Перетащить для изменения порядка: ${name}`} title="Перетащите или используйте стрелки" className="cursor-grab rounded p-1 text-[#5f7580] focus-visible:ring-2 focus-visible:ring-[#2e8175] active:cursor-grabbing" onDragStart={event => { setDraggedId(id); event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(id)); }} onDragEnd={() => setDraggedId(null)}><GripVertical className="h-5 w-5" /></button>
        <FileText className="h-5 w-5 shrink-0 text-[#2e8175]" />
        <div className="min-w-0 flex-1"><p className="break-words text-sm font-semibold text-[#294555]">{name}</p><p className="text-xs text-[#5f7580]">{item ? [...(kind === 'certificate' ? [item.category_name || 'Без категории'] : []), item.is_active ? 'Активен' : 'Отключён — скрыт на сайте'].join(' · ') : 'Загрузка данных…'}</p></div>
        <div className="flex gap-1"><Button variant="outline" size="icon" disabled={index === 0} aria-label={`Переместить выше: ${name}`} onClick={() => move(index, -1)}><ArrowUp className="h-4 w-4" /></Button><Button variant="outline" size="icon" disabled={index === ids.length - 1} aria-label={`Переместить ниже: ${name}`} onClick={() => move(index, 1)}><ArrowDown className="h-4 w-4" /></Button><Button variant="ghost" size="icon" aria-label={`Убрать: ${name}`} onClick={() => onChange(ids.filter(value => value !== id))}><X className="h-4 w-4" /></Button></div>
      </li>;
    })}</ol>
    {pickerOpen && <MaterialPicker kind={kind} initialIds={ids} onClose={() => setPickerOpen(false)} onApply={(nextIds, records) => { setItems(current => ({ ...current, ...records })); onChange(nextIds); setPickerOpen(false); }} />}
    {createOpen && <MaterialFormModal kind={kind} open showUsage={false} onClose={() => setCreateOpen(false)} onSaved={created} />}
  </section>;
}

function MaterialPicker({ kind, initialIds, onClose, onApply }: { kind: MaterialKind; initialIds: number[]; onClose: () => void; onApply: (ids: number[], items: Record<number, Material>) => void }) {
  const [selected, setSelected] = useState(initialIds);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [page, setPage] = useState(1);
  const [categories, setCategories] = useState<MaterialCategory[]>([]);
  const [result, setResult] = useState<{ key: string; items: Material[]; total: number } | null>(null);
  const [records, setRecords] = useState<Record<number, Material>>({});
  const [error, setError] = useState<string | null>(null);
  const [categoryError, setCategoryError] = useState<string | null>(null);
  const [retry, setRetry] = useState(0);
  const queryKey = JSON.stringify([kind, search, category, page, retry]);
  const loading = result?.key !== queryKey;
  useEffect(() => {
    let cancelled = false;
    if (kind !== 'certificate') return;
    materialsApi.categories(kind).then(value => { if (!cancelled) { setCategories(value.items); setCategoryError(null); } }).catch(reason => { if (!cancelled) setCategoryError(messageFromError(reason, 'Не удалось загрузить категории')); });
    return () => { cancelled = true; };
  }, [kind, retry]);
  useEffect(() => {
    let cancelled = false;
    const timer = window.setTimeout(() => {
      materialsApi.list(kind, { search, category_id: category, page, per_page: 25 }).then(value => {
        if (cancelled) return;
        setResult({ key: queryKey, items: value.items, total: value.total });
        setRecords(current => ({ ...current, ...Object.fromEntries(value.items.map(item => [item.id, item])) }));
        setError(null);
      }).catch(reason => { if (!cancelled) { setResult({ key: queryKey, items: [], total: 0 }); setError(messageFromError(reason, 'Не удалось загрузить материалы')); } });
    }, 200);
    return () => { cancelled = true; window.clearTimeout(timer); };
  }, [kind, search, category, page, queryKey]);
  return <Modal open title={kind === 'certificate' ? 'Выбрать сертификаты' : 'Выбрать вопросы и ответы'} maxWidth="max-w-3xl" onClose={onClose} footer={<><span className="mr-auto self-center text-sm text-[#5f7580]">Выбрано: {selected.length}</span><Button variant="outline" onClick={onClose}>Отмена</Button><Button onClick={() => onApply(selected, records)}>Применить выбор</Button></>}>
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-2"><Field label="Поиск"><input autoFocus type="search" className={inputClass} value={search} placeholder="Название или текст" onChange={event => { setSearch(event.target.value); setPage(1); }} onKeyDown={event => { if (event.key === 'Enter') event.preventDefault(); }} /></Field>{kind === 'certificate' && <Field label="Категория"><select className={inputClass} value={category} onChange={event => { setCategory(event.target.value); setPage(1); }}><option value="">Все категории</option><option value="0">Без категории</option>{categories.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>}</div>
      <ErrorAlert>{error || categoryError}</ErrorAlert>{(error || categoryError) && <Button variant="outline" size="sm" onClick={() => setRetry(value => value + 1)}>Повторить загрузку</Button>}
      {loading ? <p role="status" className="py-8 text-center text-sm text-[#5f7580]">Загрузка…</p> : <div className="space-y-2">{result?.items.map(item => <label key={item.id} className="flex cursor-pointer items-start gap-3 rounded-xl border border-[#dfece9] p-3"><input type="checkbox" className="mt-1 accent-[#2e8175]" checked={selected.includes(item.id)} onChange={() => setSelected(current => toggle(current, item.id))} /><span className="min-w-0 text-sm text-[#294555]"><span className="block break-words font-semibold">{item.title || item.question}</span><span className="block text-xs text-[#5f7580]">{kind === 'certificate' && `${item.category_name || 'Без категории'} · `}{item.is_active ? 'Активен' : 'Отключён'}</span></span></label>)}{!error && result?.items.length === 0 && <p className="py-8 text-center text-sm text-[#5f7580]">Материалы не найдены.</p>}</div>}
      <div className="flex items-center justify-between gap-2"><Button variant="outline" size="sm" disabled={page === 1 || loading} onClick={() => setPage(value => value - 1)}>Назад</Button><span className="text-xs text-[#5f7580]">Страница {page}{!loading && ` из ${Math.max(1, Math.ceil((result?.total ?? 0) / 25))}`}</span><Button variant="outline" size="sm" disabled={loading || page * 25 >= (result?.total ?? 0)} onClick={() => setPage(value => value + 1)}>Далее</Button></div>
    </div>
  </Modal>;
}
