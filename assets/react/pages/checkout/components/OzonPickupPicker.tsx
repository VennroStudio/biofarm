import { useEffect, useState } from 'react';
import { request } from '../../../site/api';
import { Input, Label } from '../../../site/ui';
import { RadioOption } from './RadioOption';

export type OzonPickupPoint = { id: number; address: string };

export function OzonPickupPicker({ selected, onSelect, disabled, endpoint }: {
  selected: OzonPickupPoint | null;
  onSelect: (point: OzonPickupPoint) => void;
  disabled: boolean;
  endpoint: string;
}) {
  const [points, setPoints] = useState<OzonPickupPoint[] | null>(null);
  const [search, setSearch] = useState('');
  const [error, setError] = useState('');
  const [attempt, setAttempt] = useState(0);
  const query = search.trim();
  const canSearch = query.length >= 3;
  useEffect(() => {
    if (!canSearch) return;
    const controller = new AbortController();
    const url = `${endpoint}?q=${encodeURIComponent(query)}`;
    const timer = window.setTimeout(() => {
      void request<OzonPickupPoint[]>(url, { signal: controller.signal })
        .then(result => {
          if (!controller.signal.aborted) setPoints(result);
        })
        .catch(cause => {
          if (!controller.signal.aborted) {
            setError(cause instanceof Error ? cause.message : 'Не удалось загрузить пункты выдачи.');
          }
        });
    }, 250);
    return () => { window.clearTimeout(timer); controller.abort(); };
  }, [attempt, canSearch, endpoint, query]);

  return <div className="space-y-3 rounded-xl border border-border p-4">
    {selected && <p className="text-sm">{selected.address}</p>}
    <Label htmlFor="ozon-pickup-search">Поиск по адресу</Label>
    <Input id="ozon-pickup-search" value={search} disabled={disabled} autoComplete="off"
      placeholder="Город, улица или номер дома"
      onChange={event => {
        if (event.target.value.trim() !== query) {
          setPoints(null);
          setError('');
        }
        setSearch(event.target.value);
      }} />
    {canSearch && points === null && !error && <p className="text-sm text-muted-foreground" role="status">Загружаем пункты выдачи…</p>}
    {error && <div className="space-y-2">
      <p className="text-sm text-destructive" role="alert">{error}</p>
      <button type="button" className="text-sm font-medium text-primary underline" onClick={() => {
        setError('');
        setAttempt(value => value + 1);
      }}>Повторить</button>
    </div>}
    {points && <>
      <fieldset className="max-h-72 space-y-2 overflow-y-auto" aria-label="Пункты выдачи" disabled={disabled}>
        {points.map(point => <RadioOption key={point.id} name="ozon-pickup-point" value={String(point.id)}
          checked={selected?.id === point.id} onChange={() => onSelect(point)}>
          <span className="text-sm">{point.address}</span>
        </RadioOption>)}
        {points.length === 0 && <p className="text-sm text-muted-foreground">Пункты выдачи не найдены.</p>}
      </fieldset>
    </>}
  </div>;
}
