import { useEffect, useState } from 'react';
import { request } from '../../../api/client';
import { formatDate, formatMoney, messageFromError } from '../../../shared/lib';
import { Badge, Button, ErrorAlert } from '../../../shared/ui';
import { label, money, Rows, type Dashboard, type Row } from '../../../../program/shared';
import { orderStatusLabels, paymentStatusOptions, labelByValue, normalizePaymentStatus } from '../../orders/model/orderOptions';

export type UserSection = 'profile' | 'orders' | 'referrals' | 'team' | 'shopping' | 'commission' | 'withdrawals';
type Details = { balances: Dashboard['balances']; items: Row[]; page: number; limit: number; count: number };
const titles: Record<UserSection, string> = { profile: 'Сохранённые адреса', orders: 'Личные заказы', referrals: 'Рефералы пользователя', team: 'Участники команды', shopping: 'Покупательские бонусы', commission: 'Денежные комиссии', withdrawals: 'Заявки на выплату' };
const descriptions: Partial<Record<UserSection, string>> = {
  orders: 'Заказы, оформленные под этим аккаунтом. Покупки гостей не привязываются по совпадению имени или email.',
  referrals: 'Зарегистрированные покупатели, закреплённые за ссылкой пользователя. Участники команды показаны отдельно. Заказы гостей отражаются в начислениях.',
  team: 'Пользователи, вступившие по приглашению в команду. Ставшие партнёрами сюда не входят.',
  shopping: 'Бонусы за покупки и приглашения. Используются для оплаты товаров; вывести их деньгами нельзя.',
  commission: 'Доход от покупок приглашённых клиентов и команды. Доступный баланс можно запросить к выплате.',
  withdrawals: 'История заявок пользователя. Обработка заявок — в разделе «Партнёрская программа → Выплаты».',
};
const columns: Record<UserSection, [string, string][]> = {
  profile: [], orders: [['id','Заказ'],['created_at','Дата'],['total','Сумма'],['status','Статус'],['payment_status','Оплата']],
  referrals: [['name','Покупатель'],['created_at','Регистрация'],['orders_count','Заказы'],['paid_total','Оплаченные заказы']],
  team: [['name','Участник'],['created_at','Регистрация'],['orders_count','Заказы'],['paid_total','Оплаченные заказы']],
  shopping: [['created_at','Дата'],['kind','Операция'],['state','Состояние'],['order_id','Заказ'],['amount_minor','Сумма']],
  commission: [['created_at','Дата'],['kind','Операция'],['state','Состояние'],['order_id','Заказ'],['amount_minor','Сумма']],
  withdrawals: [['created_at','Дата'],['amount_minor','Сумма'],['status','Состояние']],
};
function Status({ value, title }: { value: unknown; title?: string }) {
  const status = String(value);
  const tone = ['available','paid','completed','delivered'].includes(status) ? 'green' : ['rejected','void','cancelled','failed'].includes(status) ? 'red' : ['reserved','shipped','processing','refunded'].includes(status) ? 'blue' : 'amber';
  return <Badge tone={tone} className="whitespace-nowrap">{title ?? label(value)}</Badge>;
}

export function UserActivity({ userId, section, revision }: { userId: number; section: UserSection; revision: boolean }) {
  const [page, setPage] = useState(1);
  const [data, setData] = useState<Details | null>(null);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    let live = true;
    void request<Details>(`/admin/api/users/${userId}/details?section=${section}&page=${page}`).then(result => { if (live) setData(result); }).catch(e => { if (live) setError(messageFromError(e, 'Не удалось загрузить данные')); });
    return () => { live = false; };
  }, [userId, section, page, revision, retry]);
  return <section className="space-y-4">
    <div><h3 className="text-lg font-semibold text-[#294555]">{titles[section]}</h3>{descriptions[section] && <p className="mt-2 text-sm text-[#5f7580]">{descriptions[section]}</p>}</div>
    {error ? <><ErrorAlert>{error}</ErrorAlert><Button type="button" variant="outline" onClick={() => { setData(null); setError(''); setRetry(n => n + 1); }}>Повторить загрузку</Button></> : !data ? <p role="status">Загрузка…</p> : <>
      {(section === 'shopping' || section === 'commission') && <>
        <div className="grid grid-cols-2 gap-3 xl:grid-cols-4">{([['availableMinor','Доступно'],['pendingMinor','Ожидает'],['reservedMinor','Зарезервировано'],['debtMinor','Долг']] as const).map(([key,title]) => <div key={key} className="rounded-xl border border-[#dfece9] bg-[#f5faf8] p-4"><p className="text-xs text-[#5f7580]">{title}</p><p className="mt-2 text-xl font-semibold text-[#2e8175]">{money(key === 'availableMinor' ? Math.max(0, data.balances[section][key]) : data.balances[section][key])}</p></div>)}</div>
        <p className="text-xs leading-relaxed text-[#5f7580]">Ожидает — начисление удерживается до доставки и окончания срока. Доступно — учтено в балансе{section === 'commission' ? ', деньги ещё не выплачены' : ''}. Зарезервировано — временно заблокировано. Отменено — не учитывается в балансе.</p>
      </>}
      {section === 'profile' ? <div className="grid gap-3 sm:grid-cols-2">{data.items.length ? data.items.map(row => <div key={String(row.id)} className="rounded-xl border border-[#dfece9] p-4 text-sm"><p className="font-semibold">{String(row.label || 'Адрес')} {Boolean(Number(row.is_default)) && <Badge>Основной</Badge>}</p><p className="mt-2">{[row.postal_code,row.city,row.address].filter(Boolean).join(', ')}</p><p className="mt-1 text-[#5f7580]">{[row.name,row.phone].filter(Boolean).join(' · ')}</p></div>) : <p className="text-sm text-[#5f7580]">Адресов пока нет</p>}</div> : <>
        <Rows rows={data.items} columns={columns[section]} renderCell={(key,row) => {
          if (key === 'created_at') return formatDate(String(row[key]));
          if (key === 'name') return <div><p className="font-semibold">{[row.first_name,row.last_name].filter(Boolean).join(' ') || 'Имя не указано'}</p><p className="text-xs text-[#5f7580]">{String(row.email)}</p></div>;
          if (key === 'total' || key === 'paid_total') return formatMoney(Number(row[key]));
          if (key === 'payment_status') return <Status value={row[key]} title={labelByValue(paymentStatusOptions, normalizePaymentStatus(String(row[key])))} />;
          if (key === 'status' || key === 'state') return <Status value={row[key]} title={section === 'orders' ? orderStatusLabels[String(row[key])] : undefined} />;
          if (key === 'order_id' || key === 'id') return <span className="break-all text-xs">{String(row[key] || '—')}</span>;
          return undefined;
        }} actions={section === 'withdrawals' ? row => <details className="text-sm"><summary className="cursor-pointer text-[#2e8175]">Подробности</summary><dl className="mt-2 min-w-48 space-y-2">{([['recipient','Получатель'],['bank','Банк'],['account','Счёт']] as const).map(([key,title]) => <div key={key}><dt className="text-xs text-[#5f7580]">{title}</dt><dd className="break-all">{String((row.details as Row)?.[key] || '—')}</dd></div>)}{Boolean(row.reference) && <div>Подтверждение: {String(row.reference)}</div>}{Boolean(row.reason) && <div>Причина отказа: {String(row.reason)}</div>}</dl></details> : undefined} />
        {data.count > data.limit && <div className="flex items-center gap-3"><Button type="button" variant="outline" disabled={page === 1} onClick={() => { setData(null); setPage(page - 1); }}>Назад</Button><span className="text-sm">{page} из {Math.ceil(data.count / data.limit)}</span><Button type="button" variant="outline" disabled={page * data.limit >= data.count} onClick={() => { setData(null); setPage(page + 1); }}>Далее</Button></div>}
      </>}
    </>}
  </section>;
}
