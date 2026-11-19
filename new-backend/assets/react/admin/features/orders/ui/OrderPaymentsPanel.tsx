import { useEffect, useRef, useState, type FormEvent } from 'react';
import { RefreshCw } from 'lucide-react';
import { request } from '../../../api/client';
import { messageFromError } from '../../../shared/lib';
import { Button, ErrorAlert, Field, inputClass } from '../../../shared/ui';

type Operation = { id: string; kind: string; status: string; amount_minor: number; created_at: string; posting_number?: string | null };
type PaymentDetails = {
  payment: { configured: boolean; status: string; orderPaymentStatus: string; amountMinor: number };
  items: { id: number; product_name: string; quantity: number; has_marking?: boolean }[];
  operations: Operation[];
  ozonDelivery?: { orderNumber?: string; error?: string; status: string } | null;
};
type RefundAttempt = { id: string; body: { items: { itemId: number; quantity: number; markCodes?: string[] }[]; refundDelivery: boolean } };
const labels: Record<string, string> = {
  not_started: 'Не проводилась через ЮKassa', creating: 'Создаётся', pending: 'Ожидает обработки',
  waiting_for_capture: 'Ожидает подтверждения списания', succeeded: 'Успешно', canceled: 'Отменено',
  queued: 'В очереди', waiting_refund: 'Ожидает возврата', retry_required: 'Нужна повторная проверка',
  review_required: 'Нужна ручная сверка', skipped: 'Не требуется', not_required: 'Не требуется',
};
const kinds: Record<string, string> = { payment: 'Оплата', refund: 'Возврат', receipt: 'Чек после доставки' };
const ozonLabels: Record<string, string> = {
  awaiting_payment: 'Ожидает оплаты', queued: 'Готовится к передаче в Ozon', submitting: 'Передаётся в Ozon',
  submitted: 'Передан в Ozon', delivered: 'Выдан', review: 'Требуется проверка', submit_review: 'Проверьте создание заказа в Ozon',
  cancel_requested: 'Отмена в очереди', cancel_sending: 'Запрашиваем отмену',
  cancel_pending: 'Ожидаем подтверждения отмены', cancel_confirmed: 'Отменён, ожидаем возврата',
  cancelled: 'Отменён, деньги возвращены', partial_refund: 'Частичная выдача, ожидаем возврата',
};
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
      items: data.items.map((item) => {
        const markCodes = String(form.get(`marks${item.id}`) ?? '').split(/\r?\n/).map(code => code.trim()).filter(Boolean);
        return { itemId: Number(item.id), quantity: Number(form.get(`item${item.id}`)), ...(markCodes.length ? { markCodes } : {}) };
      })
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
      const result = await request<{ ozonStatus?: string }>(`${url}/refund`, { method: 'POST', body: { ...current.body, requestId: current.id } });
      setNotice(result.ozonStatus ? ozonLabels[result.ozonStatus] : 'Запрос возврата принят. Результат указан в истории операций.');
      await refresh();
    });
  }

  const canRefund = data?.payment.configured && data.payment.status === 'succeeded' && data.payment.orderPaymentStatus !== 'refunded';
  const ozon = data?.ozonDelivery;
  const canCancel = ozon && canRefund && ['queued', 'submitted'].includes(ozon.status);
  const receiptsForReview = (data?.operations ?? []).filter(
    (operation) => operation.kind === 'receipt' && ['canceled', 'review_required', 'retry_required'].includes(operation.status),
  );

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
        {ozon && <>
          <p className="text-sm">{ozonLabels[ozon.status] ?? ozon.status}{ozon.orderNumber ? ` · Ozon № ${ozon.orderNumber}` : ''}</p>
          {ozon.error && <p className="text-sm text-[#9c3b25]">{ozon.error}</p>}
          {ozon.status === 'submit_review' && <form className="space-y-3" onSubmit={event => {
            event.preventDefault();
            const form = new FormData(event.currentTarget);
            void run(async () => {
              await request(`${url}/ozon-resolution`, { method: 'POST', body: {
                externalNumber: String(form.get('externalNumber') ?? ''),
                confirmedMissing: form.get('confirmedMissing') === 'on',
                reason: String(form.get('reason') ?? ''),
              } });
              await refresh();
            });
          }}>
            <Field label="Номер найденного заказа Ozon"><input name="externalNumber" className={inputClass} /></Field>
            <label className="flex items-center gap-2 text-sm"><input name="confirmedMissing" type="checkbox" />В Ozon подтверждено, что заказ не создан. Вернуть деньги.</label>
            <Field label="Комментарий по результату сверки"><input name="reason" required minLength={8} maxLength={500} className={inputClass} /></Field>
            <Button type="submit" variant="outline" disabled={busy}>Сохранить результат сверки</Button>
          </form>}
          {canCancel && <Button variant="danger" disabled={busy} onClick={() => {
            if (!window.confirm('Отменить весь заказ и вернуть деньги после подтверждения Ozon?')) return;
            void run(async () => {
              const result = await request<{ ozonStatus: string }>(`${url}/refund`, { method: 'POST', body: {
                items: data.items.map(item => ({ itemId: Number(item.id), quantity: Number(item.quantity) })),
                refundDelivery: true,
              } });
              setNotice(ozonLabels[result.ozonStatus] ?? 'Запрос отмены принят.');
              await refresh();
            });
          }}>Отменить заказ Ozon</Button>}
        </>}
        {!ozon && <p className="text-sm text-[#5f7580]">При подтверждении оплаты ЮKassa статус заказа обновляется автоматически. Доставку отмечайте во вкладке «Данные заказа».</p>}
      </section>

      <section className="space-y-3">
        <h3 className="font-semibold">История платежа, возвратов и чеков</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-[#f4faf8]"><tr>{['Дата', 'Операция', 'Состояние', 'Сумма'].map((title) => <th key={title} className="p-3">{title}</th>)}</tr></thead>
            <tbody>{data?.operations.map((operation) => <tr key={operation.id} className="border-b border-[#dfece9]">
              <td className="p-3">{operation.created_at}</td>
              <td className="p-3">
                {kinds[operation.kind] ?? operation.kind}
                {operation.posting_number && <div className="text-xs">{operation.posting_number}</div>}
              </td>
              <td className="p-3">{labels[operation.status] ?? operation.status}</td>
              <td className="whitespace-nowrap p-3">{operation.kind === 'receipt' && !operation.amount_minor ? '—' : money(operation.amount_minor)}</td>
            </tr>)}</tbody>
          </table>
        </div>
        {!loading && !data?.operations.length && <p className="text-sm text-[#5f7580]">Операций ЮKassa пока нет.</p>}
      </section>

      {data && (!ozon || ozon.status === 'delivered') && <details className="rounded-xl border border-[#dfece9] p-4">
        <summary className="cursor-pointer font-semibold">Возврат денег</summary>
        <form className="mt-4 space-y-4" onSubmit={(event) => void refund(event)}>
          <p className="text-sm text-[#5f7580]">Укажите количество возвращаемых товаров. Сумма рассчитывается с учётом скидок, бонусов и предыдущих возвратов. После подтверждения возврата связанные начисления корректируются автоматически.</p>
          {!canRefund && <p className="text-sm">Нужен оплаченный через ЮKassa заказ и настроенное подключение. Для полностью возвращённого заказа новый возврат недоступен.</p>}
          <fieldset disabled={!canRefund || busy} className="space-y-4 disabled:opacity-60">
            {data.items.map((item) => <div key={item.id} className="space-y-2">
              <Field label={`${item.product_name} (в заказе ${item.quantity})`}>
                <input key={`${item.id}-${attempt?.id ?? 'new'}`} name={`item${item.id}`} type="number" min="0" max={item.quantity} step="1" defaultValue={attempt?.body.items.find((entry) => entry.itemId === Number(item.id))?.quantity ?? 0} className={inputClass} />
              </Field>
              {item.has_marking && <Field label="Коды возвращаемых экземпляров при частичном возврате">
                <textarea name={`marks${item.id}`} key={attempt?.id ?? 'new'} rows={2}
                  defaultValue={attempt?.body.items.find(entry => entry.itemId === Number(item.id))?.markCodes?.join('\n')}
                  placeholder="По одному коду на строку" className={inputClass} />
              </Field>}
            </div>)}
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
        <summary className="cursor-pointer font-semibold">Чеки после доставки</summary>
        <div className="mt-4 space-y-4">
          <p className="text-sm text-[#5f7580]">После выдачи каждого отправления Ozon чек зачёта предоплаты отправляется автоматически.</p>
          <Button variant="outline" disabled={busy || !data?.payment.configured} onClick={() => void run(async () => {
            const result = await request<{ status: string }>(`${url}/receipt`, { method: 'POST', body: {} });
            setNotice(`Статус проверки чеков — ${labels[result.status] ?? result.status}.`); await refresh();
          })}>Проверить чеки</Button>
          {receiptsForReview.length > 0 && <details>
            <summary className="cursor-pointer text-sm">Привязать уже зарегистрированный чек ЮKassa</summary>
            <form className="mt-3 space-y-3" onSubmit={(event) => {
              event.preventDefault(); const form = new FormData(event.currentTarget);
              void run(async () => {
                await request(`${url}/receipt`, { method: 'POST', body: {
                  receiptOperationId: String(form.get('receiptOperationId') ?? ''),
                  providerReceiptId: String(form.get('providerReceiptId') ?? ''),
                  reason: String(form.get('reason') ?? ''),
                } });
                setNotice('Чек проверен и привязан.'); await refresh();
              });
            }}>
              <Field label="Чек для сверки">
                <select name="receiptOperationId" required className={inputClass}>
                  {receiptsForReview.map((operation) => <option value={operation.id} key={operation.id}>
                    {operation.posting_number ?? 'Чек заказа'}
                  </option>)}
                </select>
              </Field>
              <Field label="ID чека ЮKassa"><input name="providerReceiptId" required maxLength={64} className={inputClass} /></Field>
              <Field label="Причина ручной сверки"><input name="reason" required maxLength={2000} className={inputClass} /></Field>
              <Button disabled={busy || !data?.payment.configured} type="submit">Проверить и привязать</Button>
            </form>
          </details>}
        </div>
      </details>
    </div>
  );
}
