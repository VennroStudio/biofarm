import { useEffect, useState } from 'react';
import { request } from '../../../site/api';
import { formatDate, formatMoney } from '../../../site/format';
import { PartnerDialog } from './PartnerDialog';

type OfferDetails = {
    id: string; title: string; isActive: boolean; isExpired: boolean; expiresAt: string | null; createdAt: string;
    total: number | null;
    items: { productId: number; name: string; image: string | null; quantity: number; price: number | null; total: number | null; available: boolean }[];
    orders: { total: number; page: number; pages: number; items: {
        id: string; offerId: string; customer_name: string | null; status: string; payment_status: string; total: number; created_at: string;
    }[] };
};

const orderLabels: Record<string, string> = { pending: 'Ожидает', processing: 'Обработка', shipped: 'Отправлен', delivered: 'Доставлен', cancelled: 'Отменён' };
const paymentLabels: Record<string, string> = { pending: 'Не оплачен', completed: 'Оплачен', paid: 'Оплачен', failed: 'Ошибка оплаты', refunded: 'Возврат' };
const pagerClass = 'rounded-xl border border-border px-4 py-2 text-sm hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed';

export function OfferDetailsDialog({ offerId, onClose }: { offerId: string; onClose: () => void }) {
    const [data, setData] = useState<OfferDetails | null>(null);
    const [page, setPage] = useState(1);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);
    const [revision, setRevision] = useState(0);
    useEffect(() => {
        let live = true;
        void request<OfferDetails>(`/v1/program/offers/${encodeURIComponent(offerId)}?page=${page}`)
            .then((result) => { if (live) setData(result); })
            .catch((e) => { if (live) setError(String(e)); })
            .finally(() => { if (live) setLoading(false); });
        return () => { live = false; };
    }, [offerId, page, revision]);
    function changePage(next: number) { setLoading(true); setError(''); setPage(next); }
    return <PartnerDialog title="Информация о корзине" onClose={onClose} wide>
        {error && <div role="alert" className="my-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">
            <p>{error}</p>
            <button className="mt-2 underline" onClick={() => { setError(''); setLoading(true); setRevision((n) => n + 1); }}>Повторить загрузку</button>
        </div>}
        {loading && <p role="status" className="py-4 text-sm text-muted-foreground">Загрузка…</p>}
        {data && <div className="mt-5 space-y-6">
            <div className="rounded-xl bg-secondary/50 p-4">
                <h3 className="break-words font-semibold">{data.title}</h3>
                <p className="mt-1 text-sm text-muted-foreground">{!data.isActive ? 'Отключена' : data.isExpired ? 'Срок истёк' : 'Активна'} · Создана {formatDate(data.createdAt)}{data.expiresAt ? ` · До ${formatDate(data.expiresAt)}` : ''}</p>
                <p className="mt-2 break-all text-xs text-muted-foreground">ID корзины: {data.id}</p>
            </div>
            <section aria-label="Состав корзины">
                <h3 className="mb-3 font-semibold">Товары в корзине</h3>
                <ul className="divide-y divide-border">
                    {data.items.map((item) => <li key={item.productId} className="flex items-center gap-3 py-3">
                        {item.image && <img src={item.image} alt="" className="h-14 w-14 shrink-0 rounded-lg bg-secondary/40 object-contain" />}
                        <div className="min-w-0 flex-1">
                            <p className="break-words text-sm">{item.name}</p>
                            <p className="mt-1 text-sm text-muted-foreground">{item.quantity} × {item.price !== null ? formatMoney(item.price) : 'Цена недоступна'}</p>
                            {!item.available && <p className="text-xs text-amber-700">Сейчас недоступен для заказа</p>}
                        </div>
                        <strong className="shrink-0 text-sm">{item.total !== null ? formatMoney(item.total) : '—'}</strong>
                    </li>)}
                </ul>
                <div className="mt-2 flex items-center justify-between gap-4 border-t border-border pt-4">
                    <span>Сумма товаров</span><strong className="text-xl text-primary">{data.total !== null ? formatMoney(data.total) : 'Не рассчитана'}</strong>
                </div>
                <p className="mt-2 text-xs text-muted-foreground">По текущим ценам, без доставки и скидок. В заказах ниже — сумма, сохранённая при оформлении.</p>
            </section>
            <section aria-label="Связанные заказы" aria-busy={loading}>
                <h3 className="mb-3 font-semibold">Заказы из этой корзины <span className="ml-1 text-muted-foreground">{data.orders.total}</span></h3>
                {!loading && !error && <>
                    {!data.orders.total && <p className="rounded-xl bg-secondary/40 p-4 text-sm text-muted-foreground">По этой корзине ещё нет оформленных заказов.</p>}
                    <ul className="space-y-3">
                        {data.orders.items.map((order) => <li key={order.id} className="rounded-xl border border-border p-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <strong className="break-all text-sm">{order.id}</strong>
                                <span className="rounded-full bg-secondary px-3 py-1 text-xs" aria-label={`Статус заказа: ${orderLabels[order.status] || order.status}`}>{orderLabels[order.status] || order.status}</span>
                                <span className={`rounded-full px-3 py-1 text-xs ${['completed', 'paid'].includes(order.payment_status) ? 'bg-emerald-50 text-emerald-700' : ['failed', 'refunded'].includes(order.payment_status) ? 'bg-amber-50 text-amber-800' : 'bg-secondary text-muted-foreground'}`} aria-label={`Оплата: ${paymentLabels[order.payment_status] || order.payment_status}`}>{paymentLabels[order.payment_status] || order.payment_status}</span>
                            </div>
                            <p className="mt-2 break-words text-sm text-muted-foreground">{order.customer_name || 'Покупатель'} · {formatDate(order.created_at)} · <span className="whitespace-nowrap">{formatMoney(order.total)}</span></p>
                        </li>)}
                    </ul>
                </>}
                {data.orders.pages > 1 && <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <button className={pagerClass} disabled={loading || data.orders.page <= 1} onClick={() => changePage(data.orders.page - 1)}>Назад</button>
                    <span className="text-sm text-muted-foreground">{data.orders.page} из {data.orders.pages}</span>
                    <button className={pagerClass} disabled={loading || data.orders.page >= data.orders.pages} onClick={() => changePage(data.orders.page + 1)}>Далее</button>
                </div>}
            </section>
        </div>}
    </PartnerDialog>;
}
