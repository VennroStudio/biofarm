import { useEffect, useState } from 'react';
import { CreditCard, RefreshCw } from 'lucide-react';
import { request } from '../../../api/client';
import { messageFromError } from '../../../shared/lib';
import { Button, Card, ErrorAlert } from '../../../shared/ui';

type Config = { configured: boolean; receiptsEnabled: boolean };
type ReconcileResult = { id: string; checked: boolean; status?: string };

export function PaymentIntegrationCard() {
  const [config, setConfig] = useState<Config | null>(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<ReconcileResult[] | null>(null);
  useEffect(() => {
    let live = true;
    void request<Config>('/admin/api/payments/config').then((data) => { if (live) setConfig(data); })
      .catch((e) => { if (live) setError(messageFromError(e, 'Не удалось проверить настройки ЮKassa')); });
    return () => { live = false; };
  }, []);

  async function reconcile() {
    setBusy(true); setError(''); setResult(null);
    try { setResult(await request<ReconcileResult[]>('/admin/api/payments/reconcile', { method: 'POST', body: {} })); }
    catch (e) { setError(messageFromError(e, 'Не удалось выполнить сверку')); }
    finally { setBusy(false); }
  }

  return <Card className="space-y-4 p-6">
    <h2 className="flex items-center gap-2 text-xl font-semibold"><CreditCard className="h-5 w-5" />ЮKassa — оплата и чеки</h2>
    <ErrorAlert>{error}</ErrorAlert>
    {config ? <div className="flex flex-wrap gap-3 text-sm">
      <span className="rounded-lg bg-[#f4faf8] px-3 py-2">{config.configured ? 'Параметры подключения заданы' : 'ЮKassa не настроена'}</span>
      <span className="rounded-lg bg-[#f4faf8] px-3 py-2">Чеки: {config.receiptsEnabled ? 'включены' : 'выключены'}</span>
    </div> : <p>Проверка настроек…</p>}
    <p className="text-sm text-[#5f7580]">Подключение и отправка чеков настраиваются на сервере. Статус оплаты обновляется по уведомлениям ЮKassa. Для автоматической обработки очереди чеков и повторных попыток требуется фоновый запуск сверки.</p>
    <p className="text-sm text-[#5f7580]">Сверка проверяет незавершённые платежи, возвраты и чеки; при необходимости повторяет сохранённые запросы. Оплата и возвраты конкретного заказа находятся в разделе «Заказы» → окно заказа → «Оплата и возвраты».</p>
    <Button variant="outline" disabled={busy || !config?.configured} onClick={() => void reconcile()}><RefreshCw className="h-4 w-4" />{busy ? 'Сверка…' : 'Сверить незавершённые операции'}</Button>
    {result && <div role="status" className="rounded-xl bg-[#f4faf8] p-3 text-sm">
      {result.length === 0 ? 'Незавершённых операций для сверки нет.' : `Обработано: ${result.length}. Успешных проверок: ${result.filter((row) => row.checked).length}. Требуют внимания: ${result.filter((row) => !row.checked).length}.`}
      {result.some((row) => !row.checked) && <ul className="mt-2 list-inside list-disc">{result.filter((row) => !row.checked).map((row) => <li key={row.id}>Операция {row.id}: {row.status ?? 'проверка не завершена'}</li>)}</ul>}
    </div>}
  </Card>;
}
