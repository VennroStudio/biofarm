import { useEffect, useState } from 'react';
import { Button, ErrorAlert, inputClass, SearchField } from '../../shared/ui';
import { messageFromError } from '../../shared/lib';
import { materialsApi } from './api';
import type { Paginated, Placement } from './types';
export const placementKey = (item: Placement) => `${item.target_type}:${item.target_id}`;
export function PlacementPicker({ value, onChange }: { value: Placement[]; onChange: (value: Placement[]) => void }) {
  const [search, setSearch] = useState('');
  const [type, setType] = useState('');
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<Paginated<Placement> | null>(null);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    let active = true;
    const timer = window.setTimeout(() => {
      setResult(null); setError(null);
      materialsApi.targets({ search, target_type: type, page, per_page: 25 }).then((data) => { if (active) setResult(data); }).catch((e) => { if (active) setError(messageFromError(e, 'Не удалось загрузить страницы и товары')); });
    }, 200);
    return () => { active = false; window.clearTimeout(timer); };
  }, [search, type, page]);
  return <div className="grid gap-4">
    <div className="grid gap-2">{value.length === 0 ? <p className="text-sm text-[#5f7580]">Места использования пока не выбраны.</p> : value.map((item) => <div key={placementKey(item)} className="flex items-center justify-between gap-3 rounded-xl border border-[#dfece9] p-3"><div><p className="text-sm font-semibold">{item.title}</p>{item.url && <a href={item.url} target="_blank" rel="noreferrer" className="text-xs text-[#2e8175]">Открыть страницу</a>}</div><Button type="button" variant="ghost" onClick={() => onChange(value.filter((current) => placementKey(current) !== placementKey(item)))}>Убрать</Button></div>)}</div>
    <div className="flex flex-wrap gap-3"><SearchField value={search} onChange={(next) => { setSearch(next); setPage(1); }} placeholder="Найти страницу или товар..." /><select aria-label="Тип места использования" className={inputClass} value={type} onChange={(e) => { setType(e.target.value); setPage(1); }}><option value="">Страницы и товары</option><option value="page">Страницы</option><option value="product">Товары</option></select></div>
    <ErrorAlert>{error}</ErrorAlert>
    <div className="grid max-h-64 gap-1 overflow-y-auto">{!result && !error ? <p className="text-sm">Загрузка...</p> : result?.items.length === 0 ? <p className="text-sm">Ничего не найдено.</p> : result?.items.map((item) => { const selected = value.some((current) => placementKey(current) === placementKey(item)); return <label key={placementKey(item)} className="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-[#f5faf8]"><input type="checkbox" checked={selected} onChange={() => onChange(selected ? value.filter((current) => placementKey(current) !== placementKey(item)) : [...value, item])} /><span className="text-sm">{item.title}<span className="ml-2 text-xs text-[#5f7580]">{item.target_type === 'product' ? 'Товар' : 'Страница'}</span></span></label>; })}</div>
    {result && result.total > result.per_page && <div className="flex items-center gap-3"><Button type="button" variant="outline" disabled={page === 1} onClick={() => setPage(page - 1)}>Назад</Button><span className="text-sm">{page} / {Math.ceil(result.total / result.per_page)}</span><Button type="button" variant="outline" disabled={page * result.per_page >= result.total} onClick={() => setPage(page + 1)}>Далее</Button></div>}
  </div>;
}
