import { useEffect, useRef, useState, type FormEvent } from 'react';
import { RefreshCw } from 'lucide-react';
import { request } from '../../../api/client';
import { messageFromError } from '../../../shared/lib';
import { Button, ErrorAlert, Field, inputClass } from '../../../shared/ui';

type Operation = { id: string; kind: string; status: string; amount_minor: number; created_at: string };
type PaymentDetails = {
  payment: { configured: boolean; status: string; orderPaymentStatus: string; amountMinor: number };
  items: { id: number; product_name: string; quantity: number }[];
  operations: Operation[];
};
type RefundAttempt = { id: string; body: { items: { itemId: number; quantity: number }[]; refundDelivery: boolean } };
const labels: Record<string, string> = {
  not_started: 'Не проводилась через ЮKassa', creating: 'Создаётся', pending: 'Ожидает обработки',
  waiting_for_capture: 'Ожидает подтверждения списания', succeeded: 'Успешно', canceled: 'Отменено',
  queued: 'В очереди', waiting_refund: 'Ожидает возврата', retry_required: 'Нужна повторная проверка',
  review_required: 'Нужна ручная сверка', skipped: 'Не требуется', not_required: 'Не требуется',
};
const kinds: Record<string, string> = { payment: 'Оплата', refund: 'Возврат', receipt: 'Чек после доставки' };
const money = (minor: number) => (Number(minor) / 100).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' });

export function OrderPaymentsPanel({ orderId, onPaymentChange }: {
  orderId: string;
  onPaymentChange: (status: string) => void;
}) {
  const [data, setData] = useState<PaymentDetails | null>(null);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [attempt, setAttempt] = useState<RefundAttempt | null>(null);
  const locked = useRef(false);
  const callback = useRef(onPaymentChange);
  useEffect(() => { callback.current = onPaymentChange; }, [onPaymentChange]);
  const url = `/admin/api/payments/orders/${encodeURIComponent(orderId)}`;
  const storageKey = `biofarm_refund_${orderId}`;

  useEffect(() => {
    let live = true;
    void request<PaymentDetails>(url).then((result) => {
      if (!live) return;
      setData(result);
      callback.current(result.payment.orderPaymentStatus);
      const saved = sessionStorage.getItem(storageKey);
      if (saved) setAttempt(JSON.parse(saved) as RefundAttempt);
    }).catch((e) => { if (live) setError(messageFromError(e, 'Не удалось загрузить оплату')); })
      .finally(() => { if (live) setLoading(false); });
    return () => { live = false; };
  }, [url, storageKey]);

  async function refresh() {
    const result = await request<PaymentDetails>(url);
    setData(result);
    callback.current(result.payment.orderPaymentStatus);
  }

  async function run(action: () => Promise<void>) {
    if (locked.current) return;
    locked.current = true;
    setBusy(true);
    setError('');
    setNotice('');
    try { await action(); } catch (e) { setError(messageFromError(e, 'Операция не выполнена')); }
    finally { locked.current = false; setBusy(false); }
  }

  async function refund(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!data) return;
    const form = new FormData(event.currentTarget);
    const body = {
      items: data.items.map((item) => ({ itemId: Number(item.id), quantity: Number(form.get(`item${item.id}`)) }))
        .filter((item) => item.quantity > 0),
      refundDelivery: form.get('delivery') === 'on',
    };
    await run(async () => {
      if (!body.items.length && !body.refundDelivery) throw new Error('Выберите товары или возврат доставки.');
      if (attempt && JSON.stringify(attempt.body) !== JSON.stringify(body)) {
        throw new Error('Повторите прежний состав возврата или начните отдельный возврат после проверки истории.');
      }
      const current = attempt ?? { id: crypto.randomUUID(), body };
      sessionStorage.setItem(storageKey, JSON.stringify(current));
      setAttempt(current);
      await request(`${url}/refund`, { method: 'POST', body: { ...current.body, requestId: current.id } });
      setNotice('Запрос возврата принят. Результат указан в истории операций.');
      await refresh();
    });
  }

  const canRefund = data?.payment.configured && data.payment.status === 'succeeded' && data.payment.orderPaymentStatus !== 'refunded';
  return (
    <div className="space-y-5">
      <ErrorAlert>{error}</ErrorAlert>
      {notice && <p role="status" className="rounded-xl bg-[#eaf5f1] p-3 text-sm text-[#18574f]">{notice}</p>}
      <section className="space-y-3 rounded-xl border border-[#dfece9] p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h3 className="font-semibold">Оплата через ЮKassa</h3>
          <Button variant="outline" disabled={busy || loading} onClick={() => void run(async () => {
            await refresh(); setNotice('Состояние оплаты обновлено.');
          })}><RefreshCw className="h-4 w-4" />Проверить оплату</Button>
        </div>
        {loading ? <p>Загрузка оплаты…</p> : data && <>
          <p>{labels[data.payment.status] ?? data.payment.status} · {money(data.payment.amountMinor)}</p>
          {!data.payment.configured && <p className="text-sm text-[#5f7580]">ЮKassa не настроена. Онлайн-возврат и отправка чеков недоступны.</p>}
          {data.payment.status === 'not_started' && <p className="text-sm text-[#5f7580]">Отметка «Оплачен» в заказе сама по себе не создаёт платёж ЮKassa. Для такого заказа онлайн-возврат недоступен.</p>}
        </>}
        <p className="text-sm text-[#5f7580]">При подтверждении оплаты ЮKassa статус заказа обновляется автоматически. Доставку отмечайте во вкладке «Данные заказа».</p>
      </section>

      <section className="space-y-3">
        <h3 className="font-semibold">История платежа, возвратов и чеков</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-[#f4faf8]"><tr>{['Дата', 'Операция', 'Состояние', 'Сумма'].map((title) => <th key={title} className="p-3">{title}</th>)}</tr></thead>
            <tbody>{data?.operations.map((operation) => <tr key={operation.id} className="border-b border-[#dfece9]">
              <td className="p-3">{operation.created_at}</td><td className="p-3">{kinds[operation.kind] ?? operation.kind}</td>
              <td className="p-3">{labels[operation.status] ?? operation.status}</td>
              <td className="whitespace-nowrap p-3">{operation.kind === 'receipt' ? '—' : money(operation.amount_minor)}</td>
            </tr>)}</tbody>
          </table>
        </div>
        {!loading && !data?.operations.length && <p className="text-sm text-[#5f7580]">Операций ЮKassa пока нет.</p>}
      </section>

      {data && <details className="rounded-xl border border-[#dfece9] p-4">
        <summary className="cursor-pointer font-semibold">Возврат денег</summary>
        <form className="mt-4 space-y-4" onSubmit={(event) => void refund(event)}>
          <p className="text-sm text-[#5f7580]">Укажите количество возвращаемых товаров. Сумма рассчитывается с учётом скидок, бонусов и предыдущих возвратов. После подтверждения возврата связанные начисления корректируются автоматически.</p>
          {!canRefund && <p className="text-sm">Нужен оплаченный через ЮKassa заказ и настроенное подключение. Для полностью возвращённого заказа новый возврат недоступен.</p>}
          <fieldset disabled={!canRefund || busy} className="space-y-4 disabled:opacity-60">
            {data.items.map((item) => <Field key={item.id} label={`${item.product_name} (в заказе ${item.quantity})`}>
              <input key={`${item.id}-${attempt?.id ?? 'new'}`} name={`item${item.id}`} type="number" min="0" max={item.quantity} step="1" defaultValue={attempt?.body.items.find((entry) => entry.itemId === Number(item.id))?.quantity ?? 0} className={inputClass} />
            </Field>)}
            <label className="flex items-center gap-2"><input key={attempt?.id ?? 'new'} name="delivery" type="checkbox" defaultChecked={attempt?.body.refundDelivery ?? false} />Вернуть стоимость доставки</label>
            <div className="flex flex-wrap gap-2">
              <Button type="submit" variant="danger">{attempt ? 'Повторить тот же возврат' : 'Оформить возврат'}</Button>
              {attempt && <Button variant="outline" onClick={() => {
                if (!window.confirm('Проверьте историю: предыдущий возврат завершён? Начать отдельную операцию возврата?')) return;
                sessionStorage.removeItem(storageKey); setAttempt(null); setNotice('Можно оформить отдельный возврат.');
              }}>Начать отдельный возврат</Button>}
            </div>
          </fieldset>
        </form>
      </details>}

      <details className="rounded-xl border border-[#dfece9] p-4">
        <summary className="cursor-pointer font-semibold">Чек после доставки</summary>
        <div className="mt-4 space-y-4">
          <p className="text-sm text-[#5f7580]">После доставки чек зачёта предоплаты попадает в очередь. При настроенной фоновой обработке он отправляется автоматически. Здесь можно повторить отправку или проверить состояние. Ошибка чека не отменяет доставку.</p>
          <Button variant="outline" disabled={busy || !data?.payment.configured} onClick={() => void run(async () => {
            const result = await request<{ status: string }>(`${url}/receipt`, { method: 'POST', body: {} });
            setNotice(`Чек: ${labels[result.status] ?? result.status}.`); await refresh();
          })}>Отправить / проверить чек</Button>
          <details>
            <summary className="cursor-pointer text-sm">Привязать уже зарегистрированный чек ЮKassa</summary>
            <form className="mt-3 space-y-3" onSubmit={(event) => {
              event.preventDefault(); const form = new FormData(event.currentTarget);
              void run(async () => {
                await request(`${url}/receipt`, { method: 'POST', body: { providerReceiptId: String(form.get('providerReceiptId') ?? ''), reason: String(form.get('reason') ?? '') } });
                setNotice('Чек проверен и привязан.'); await refresh();
              });
            }}>
              <p className="text-sm text-[#5f7580]">Сервер проверит существующий чек у ЮKassa. Новый чек создан не будет.</p>
              <Field label="ID чека ЮKassa"><input name="providerReceiptId" required maxLength={64} className={inputClass} /></Field>
              <Field label="Причина ручной сверки"><input name="reason" required maxLength={2000} className={inputClass} /></Field>
              <Button disabled={busy || !data?.payment.configured} type="submit">Проверить и привязать</Button>
            </form>
          </details>
        </div>
      </details>
    </div>
  );
}
