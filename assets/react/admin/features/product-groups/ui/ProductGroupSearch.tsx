import { Plus } from 'lucide-react';
import { useEffect, useId, useState, type KeyboardEvent } from 'react';
import { productGroupsApi } from '../../../api/resources';
import { messageFromError } from '../../../shared/lib';
import { inputClass } from '../../../shared/ui';
import type { ProductGroupMember } from '../../../types';
import { ProductGroupThumbnail } from './ProductGroupThumbnail';

type Props = {
  selected: ProductGroupMember[];
  onAdd: (product: ProductGroupMember) => void;
};

export function ProductGroupSearch({ selected, onAdd }: Props) {
  const id = useId();
  const listId = `${id}-products`;
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const [highlighted, setHighlighted] = useState(-1);
  const [result, setResult] = useState<{ term: string; items: ProductGroupMember[]; error?: string }>();
  const term = search.trim();
  const options = result?.term === term ? result.items.filter(product => !selected.some(item => item.id === product.id)) : [];
  const activeOption = options[highlighted];

  useEffect(() => {
    if (!open || !term) return;
    let cancelled = false;
    const timer = window.setTimeout(() => {
      void productGroupsApi.searchProducts(term).then(({ items }) => {
        if (!cancelled) setResult({ term, items });
      }).catch(error => {
        if (!cancelled) setResult({ term, items: [], error: messageFromError(error, 'Не удалось найти товары') });
      });
    }, 180);
    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [open, term]);

  function pick(product: ProductGroupMember) {
    onAdd(product);
    setSearch('');
    setHighlighted(-1);
    setResult(undefined);
  }

  function handleKey(event: KeyboardEvent<HTMLInputElement>) {
    if (event.nativeEvent.isComposing) return;
    if (event.key === 'Enter') {
      event.preventDefault();
      if (open && activeOption) pick(activeOption);
    } else if (event.key === 'Escape' && open) {
      event.preventDefault();
      setOpen(false);
      setHighlighted(-1);
    } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      setOpen(true);
      if (!options.length) return;
      const next = event.key === 'ArrowDown' ? (highlighted + 1) % options.length : highlighted <= 0 ? options.length - 1 : highlighted - 1;
      setHighlighted(next);
      document.getElementById(`${listId}-${options[next].id}`)?.scrollIntoView({ block: 'nearest' });
    }
  }

  const status = !term ? 'Введите название товара'
    : result?.term !== term ? 'Ищем товары…'
      : result.error || (options.length ? undefined : result.items.length ? 'Найденные товары уже в группе' : 'Товары не найдены');

  return <div className="space-y-2" onBlur={event => {
    if (!event.currentTarget.contains(event.relatedTarget)) {
      setOpen(false);
      setHighlighted(-1);
    }
  }}>
    <label htmlFor={id} className="text-sm font-semibold text-[#294555]">Добавить товар</label>
    <input id={id} className={inputClass} type="text" role="combobox" autoComplete="off" placeholder="Поиск по названию товара"
      aria-autocomplete="list" aria-expanded={open} aria-controls={listId}
      aria-activedescendant={open && activeOption ? `${listId}-${activeOption.id}` : undefined} value={search}
      onFocus={() => setOpen(true)} onClick={() => setOpen(true)} onKeyDown={handleKey}
      onChange={event => { setSearch(event.target.value); setOpen(true); setHighlighted(-1); }} />
    <div hidden={!open} className="overflow-hidden rounded-xl border border-[#dfece9] bg-white">
      {open && status && <p role="status" className="px-3 py-3 text-sm text-[#5f7580]">{status}</p>}
      <ul id={listId} role="listbox" aria-label="Результаты поиска товаров" className="max-h-56 overflow-y-auto overscroll-contain">
        {open && options.map((product, index) => <li key={product.id} role="presentation">
          <button id={`${listId}-${product.id}`} type="button" role="option" aria-selected={highlighted === index} tabIndex={-1}
            className={`flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-[#294555] hover:bg-[#eaf5f1] ${highlighted === index ? 'bg-[#eaf5f1]' : ''}`}
            onMouseDown={event => event.preventDefault()} onClick={() => pick(product)}>
            <ProductGroupThumbnail image={product.image} />
            <span className="min-w-0 flex-1 break-words"><span className="block font-semibold">{product.name}</span>
              <span className="text-xs text-[#5f7580]">{[product.weight, product.sku, product.group_name && `В группе «${product.group_name}»`].filter(Boolean).join(' · ')}</span>
            </span>
            <Plus aria-hidden="true" className="h-4 w-4 shrink-0 text-[#2e8175]" />
          </button>
        </li>)}
      </ul>
    </div>
  </div>;
}
