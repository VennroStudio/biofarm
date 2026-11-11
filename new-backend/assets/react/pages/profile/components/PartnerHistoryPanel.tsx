import { useEffect, useState } from 'react';
import { Ban, Info, QrCode } from 'lucide-react';
import { request } from '../../../site/api';
import { formatMoney } from '../../../site/format';
import { Pager, Rows, Section, type Listing } from '../../../program/shared';
import { OfferDetailsDialog } from './OfferDetailsDialog';
import { OfferLinkDialog } from './OfferLinkDialog';

const columns: Record<'sales' | 'offers', [string, string][]> = {
  sales: [
    ['id', 'Заказ'], ['created_at', 'Дата'], ['status', 'Состояние'],
    ['payment_status', 'Оплата'], ['total', 'Сумма'],
  ],
  offers: [
    ['title', 'Название'], ['expires_at', 'Срок'], ['visits', 'Просмотры'],
    ['orders_count', 'Заказы'], ['paid_count', 'Оплачено'],
  ],
};
const actionClass = 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border text-primary hover:bg-secondary focus-visible:outline-primary disabled:opacity-35';

export function PartnerHistoryPanel({ kind }: { kind: 'sales' | 'offers' }) {
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
  const [loadedKey, setLoadedKey] = useState('');
  const [error, setError] = useState('');
  const [disabling, setDisabling] = useState('');
  const [offerId, setOfferId] = useState('');
  const [offerUrl, setOfferUrl] = useState('');
  const listKey = `${kind}:${page}:${revision}`;
  const loading = loadedKey !== listKey;
  const isSales = kind === 'sales';

  useEffect(() => {
    let live = true;
    void request<Listing>(`/v1/program/${kind}?page=${page}`)
      .then((result) => { if (live) setList(result); })
      .catch((cause: unknown) => {
        if (!live) return;
        setError(cause instanceof Error ? cause.message : 'Не удалось загрузить историю');
        setList({ items: [], page, limit: 25 });
      })
      .finally(() => { if (live) setLoadedKey(listKey); });
    return () => { live = false; };
  }, [kind, page, listKey]);

  async function disable(id: string) {
    setDisabling(id);
    setError('');
    try {
      await request(`/v1/program/offers/${encodeURIComponent(id)}`, { method: 'PATCH', body: { isActive: false } });
      setRevision((current) => current + 1);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : 'Не удалось отключить корзину');
    } finally {
      setDisabling('');
    }
  }

  function changePage(next: number) {
    setError('');
    setPage(next);
  }

  return (
    <Section title={isSales ? 'Реферальные заказы' : 'История корзин'}>
      {offerId && <OfferDetailsDialog key={offerId} offerId={offerId} onClose={() => setOfferId('')} />}
      {offerUrl && <OfferLinkDialog key={offerUrl} url={offerUrl} onClose={() => setOfferUrl('')} />}
      {error && <p role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">{error}</p>}
      {isSales && <p className="text-sm text-muted-foreground">Заказы по вашим приглашениям и QR-корзинам.</p>}
      <Rows loading={loading} rows={list.items} columns={columns[kind]}
        renderCell={(field, row) => field === 'total' ? formatMoney(Number(row.total)) : undefined}
        actions={isSales ? undefined : (row) => {
          const id = String(row.id);
          const active = Boolean(Number(row.is_active));
          return <div className="flex flex-nowrap items-center gap-2">
            <button type="button" className={actionClass} title="Информация о корзине"
              aria-label={`Информация о корзине: ${row.title}`} onClick={() => setOfferId(id)}>
              <Info className="h-5 w-5" aria-hidden="true" />
            </button>
            <button type="button" className={actionClass} title="Ссылка и QR-код"
              aria-label={`Ссылка и QR-код: ${row.title}`} disabled={!active} onClick={() => setOfferUrl(String(row.url))}>
              <QrCode className="h-5 w-5" aria-hidden="true" />
            </button>
            <button type="button" className={actionClass} title="Отключить корзину"
              aria-label={`Отключить корзину: ${row.title}`} disabled={!active || Boolean(disabling)} onClick={() => void disable(id)}>
              <Ban className="h-5 w-5" aria-hidden="true" />
            </button>
          </div>;
        }}
      />
      {!loading && <Pager page={page} setPage={changePage} hasMore={list.items.length === list.limit} />}
    </Section>
  );
}
