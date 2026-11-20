import { useEffect, useState } from 'react';
import { Button, ErrorAlert, inputClass } from '../../shared/ui';
import { messageFromError } from '../../shared/lib';
import { materialsApi } from './api';
import { placementKey } from './PlacementPicker';
import type { Paginated, Placement } from './types';

export function TargetSearchFilter({ value, onChange }: { value: Placement | null; onChange: (value: Placement | null) => void }) {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<{ key: string; data: Paginated<Placement> } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const key = JSON.stringify([search.trim(), page]);
  const searching = search.trim().length > 0;
  useEffect(() => {
    if (!searching) return;
    let active = true;
    const timer = window.setTimeout(() => {
      materialsApi.targets({ search: search.trim(), page, per_page: 25 }).then(data => {
        if (active) { setResult({ key, data }); setError(null); }
      }).catch(reason => { if (active) setError(messageFromError(reason, 'Не удалось найти место использования')); });
    }, 200);
    return () => { active = false; window.clearTimeout(timer); };
  }, [search, searching, page, key]);
  const current = result?.key === key ? result.data : null;
  return <div className="mb-5 rounded-xl border border-[#dfece9] p-3">
    <label className="grid gap-2 text-sm font-semibold text-[#526d78]">Место использования
      <input type="search" className={inputClass} value={search} placeholder="Введите название товара или страницы…" onChange={event => { setSearch(event.target.value); setPage(1); setError(null); }} />
    </label>
    {value && <div className="mt-2 flex items-center justify-between gap-3 rounded-lg bg-[#eaf5f1] px-3 py-2 text-sm"><span>{value.title} · {value.target_type === 'product' ? 'Товар' : 'Страница'}</span><Button size="sm" variant="ghost" onClick={() => { onChange(null); setSearch(''); }}>Сбросить</Button></div>}
    {searching && <div className="mt-2" aria-live="polite"><ErrorAlert>{error}</ErrorAlert>{!error && !current ? <p className="text-sm">Поиск…</p> : current && <><div className="max-h-64 overflow-y-auto">{current.items.map(target => <button type="button" key={placementKey(target)} className="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm hover:bg-[#eaf5f1] focus-visible:bg-[#eaf5f1]" onClick={() => { onChange(target); setSearch(''); }}><span>{target.title}</span><span className="shrink-0 text-xs text-[#5f7580]">{target.target_type === 'product' ? 'Товар' : 'Страница'}</span></button>)}{current.total === 0 && <p className="py-2 text-sm">Ничего не найдено.</p>}</div>{current.total > current.per_page && <div className="mt-2 flex items-center gap-3"><Button size="sm" variant="outline" disabled={page === 1} onClick={() => setPage(page - 1)}>Назад</Button><span className="text-sm">{page} / {Math.ceil(current.total / current.per_page)}</span><Button size="sm" variant="outline" disabled={page * current.per_page >= current.total} onClick={() => setPage(page + 1)}>Далее</Button></div>}</>}</div>}
  </div>;
}
