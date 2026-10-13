import { useEffect, useState } from 'react';
import { request } from '../../../site/api';
import { Section, Rows, Pager, buttonClass, type Dashboard, type Listing } from '../../../program/shared';

import { OfferLinkDialog } from './OfferLinkDialog';

const bonus = (minor: number) => new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(minor / 100) + ' бонусов';
const date = (value: unknown) => value ? new Date(String(value).replace(' ', 'T') + 'Z').toLocaleDateString('ru-RU') : '—';

export function BonusPanel({ referralEnabled }: { referralEnabled: boolean }) {
  const [inviteOpen, setInviteOpen] = useState(false);
  const [data, setData] = useState<Dashboard | null>(null);
  const [page, setPage] = useState(1);
  const [loadedPage, setLoadedPage] = useState(0);
  const [list, setList] = useState<Listing>({ items: [], page: 1, limit: 25 });
  const [error, setError] = useState('');

  useEffect(() => {
    let live = true;
    void Promise.all([
      request<Dashboard>('/v1/program'),
      request<Listing>(`/v1/program/ledger?wallet=shopping&page=${page}`),
    ]).then(([dashboard, history]) => {
      if (!live) return;
      setData(dashboard);
      setList(history);
      setError('');
    }).catch((reason: unknown) => {
      if (!live) return;
      setError(reason instanceof Error ? reason.message : 'Не удалось загрузить бонусы');
      setList({ items: [], page, limit: 25 });
    }).finally(() => { if (live) setLoadedPage(page); });
    return () => { live = false; };
  }, [page]);

  return (
    <div className="space-y-6">
      {error && <p role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">{error}</p>}
      <Section title="Покупательские бонусы">
        <p className="text-sm text-muted-foreground">Бонусы за ваши покупки и приглашения друзей. Их можно использовать для следующих заказов в магазине, вывести деньгами нельзя.</p>
        {data ? <>
          <div className="grid grid-cols-2 gap-5 xl:grid-cols-4">
            {([
              ['availableMinor', 'Доступно'],
              ['pendingMinor', 'На удержании'],
              ['reservedMinor', 'Зарезервировано'],
              ['debtMinor', 'Долг'],
            ] as const).map(([key, caption]) => (
              <div key={key}>
                <p className="text-sm text-muted-foreground">{caption}</p>
                <strong className="mt-2 block break-words text-2xl text-primary">{bonus(data.balances.shopping[key])}</strong>
              </div>
            ))}
          </div>
          <p className="text-sm">1 бонус = 1 ₽ скидки на товары. Доставка оплачивается деньгами. Срок новых начислений — {data.rates.bonusLifetimeMonths} месяцев. <a className="text-primary underline" href="/bonus-rules" target="_blank" rel="noreferrer">Правила программы</a></p>
          {data.bonusExpiry.nextExpiryAt && <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Ближайшее сгорание: {bonus(data.bonusExpiry.nextExpiryMinor)} — {date(data.bonusExpiry.nextExpiryAt)}.</p>}
          <p className="text-sm text-muted-foreground">Базовая ставка — {data.rates.buyerBps / 100}%. Начисление после подтверждённой оплаты; доступность — после доставки и удержания {data.rates.holdDays} дней. Начисления учитывают скидки, списание бонусов и возвраты.</p>
        </> : !error && <p role="status">Загрузка бонусов…</p>}
      </Section>
      {referralEnabled && data && !data.identity.canEarnCommission && <Section title="Приглашайте друзей">
        <p className="text-sm text-muted-foreground">За покупки по вашей ссылке вы получаете {data.rates.referralBonusBps / 100}% покупательскими бонусами. Вывод деньгами недоступен.</p>
        <button type="button" className={buttonClass} onClick={() => setInviteOpen(true)}>Моя ссылка и QR-код</button>
        {inviteOpen && <OfferLinkDialog url={`${window.location.origin}/?ref=${encodeURIComponent(data.identity.referralCode)}`} title="Пригласить покупателя" description="Делитесь ссылкой и получайте покупательские бонусы." onClose={() => setInviteOpen(false)} />}
      </Section>}
      <Section title="История бонусов">
        <Rows rows={list.items} loading={loadedPage !== page} columns={[
          ['created_at', 'Дата'], ['kind', 'Операция'], ['state', 'Состояние'],
          ['order_id', 'Заказ'], ['amount_minor', 'Бонусы'], ['expires_at', 'Действуют до'],
        ]} renderCell={(key, row) => key === 'expires_at' ? date(row[key]) : key === 'amount_minor' ? bonus(Number(row[key])) : undefined} />
        {loadedPage === page && <Pager page={page} setPage={setPage} hasMore={list.items.length === list.limit} />}
      </Section>
    </div>
  );
}
