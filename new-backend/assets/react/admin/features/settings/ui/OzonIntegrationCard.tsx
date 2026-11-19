import { Package, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { request } from '../../../api/client';
import { messageFromError } from '../../../shared/lib';
import { Badge, Button, Card, ErrorAlert } from '../../../shared/ui';

const endpoint = '/admin/api/integrations/ozon';

type SyncResult = {
  added: number;
  updated: number;
  archived: number;
  visible: number;
  available?: number;
  total: number;
  groups: number;
};

type OzonStatus = {
  configured: boolean;
  connected: boolean;
  redirectUri: string;
  webhookUrl: string | null;
  first: boolean;
  pending: boolean;
  lastSuccessAt: string | null;
  result: SyncResult | null;
  error: string | null;
  deliveryEnabled: boolean;
  deliveryReady: boolean;
};

export function OzonIntegrationCard() {
  const [status, setStatus] = useState<OzonStatus | null>(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [showWebhook, setShowWebhook] = useState(false);
  const pending = status?.pending ?? false;

  useEffect(() => {
    let active = true;
    async function refresh() {
      try {
        const current = await request<OzonStatus>(endpoint);
        if (active) setStatus(current);
      } catch (reason) {
        if (active) setError(messageFromError(reason, 'Не удалось загрузить настройки Ozon.'));
      }
    }
    void refresh();
    const timer = window.setInterval(() => { if (!document.hidden) void refresh(); }, pending ? 3000 : 30000);
    return () => {
      active = false;
      window.clearInterval(timer);
    };
  }, [pending]);

  async function connect() {
    setBusy(true);
    setError('');
    try {
      const { url } = await request<{ url: string }>(`${endpoint}/connect`, { method: 'POST' });
      window.location.assign(url);
    } catch (reason) {
      setError(messageFromError(reason, 'Не удалось открыть подключение Ozon.'));
      setBusy(false);
    }
  }

  async function sync() {
    if (status?.first && !window.confirm('Заменить текущий ассортимент товарами из Ozon? Старые карточки будут перенесены в архив.')) {
      return;
    }
    setBusy(true);
    setError('');
    try {
      setStatus(await request<OzonStatus>(`${endpoint}/sync`, { method: 'POST' }));
    } catch (reason) {
      setError(messageFromError(reason, 'Не удалось запустить синхронизацию Ozon.'));
    } finally {
      setBusy(false);
    }
  }

  return <Card className="space-y-4 p-6">
    <div>
      <h2 className="flex items-center gap-2 text-xl font-semibold"><Package className="h-5 w-5" aria-hidden="true" />Ozon Seller</h2>
      <p className="mt-1 text-sm text-[#5f7580]">Товары, группы, цены и остатки FBO.</p>
    </div>
    <ErrorAlert>{error || status?.error}</ErrorAlert>
    {status && <>
      <div className="flex flex-wrap items-center gap-3">
        <Badge tone={status.connected ? 'green' : 'gray'}>
          {status.connected ? 'Подключено' : status.configured ? 'Ожидает подключения' : 'Параметры не заданы'}
        </Badge>
        {status.lastSuccessAt && <span className="text-sm text-[#5f7580]">
          Последняя сверка {new Date(status.lastSuccessAt).toLocaleString('ru-RU')}
        </span>}
      </div>
      {status.deliveryEnabled && <p className="text-sm text-[#5f7580]">
        {status.deliveryReady ? 'Доставка в ПВЗ Ozon подключена.' : 'Для доставки в ПВЗ проверьте права Ozon, оплату, кассу и подтверждение телефона.'}
      </p>}
      {status.configured ? <p className="text-sm text-[#5f7580]">
        {status.first
          ? 'Первая синхронизация заменит текущий ассортимент товарами из Ozon. История заказов сохранится.'
          : 'Каталог сверяется ежедневно. Изменение остатка можно получить сразу по уведомлению Ozon.'}
      </p> : <p className="text-sm text-[#5f7580]">Укажите параметры приложения Ozon Seller на сервере.</p>}
      {status.configured && !status.connected && <p className="break-all text-sm text-[#5f7580]">
        URL для OAuth в настройках приложения Ozon <code>{status.redirectUri}</code>
      </p>}
      <div className="flex flex-wrap gap-3">
        <Button variant="outline" disabled={busy || pending || !status.configured} onClick={() => void connect()}>
          {status.connected ? 'Переподключить Ozon' : 'Подключить Ozon'}
        </Button>
        <Button disabled={busy || pending || !status.connected} onClick={() => void sync()}>
          <RefreshCw className="h-4 w-4" aria-hidden="true" />
          {pending ? 'Синхронизация…' : 'Синхронизировать каталог'}
        </Button>
      </div>
      {pending && <p role="status" className="text-sm text-[#5f7580]">Запрос принят. Результат появится после обработки.</p>}
      {status.result && <div role="status" className="rounded-xl bg-[#f4faf8] p-3 text-sm">
        <p>Всего в Ozon {status.result.total} · на витрине {status.result.visible} · {status.result.available !== undefined ? `в наличии ${status.result.available} · ` : ''}групп {status.result.groups}</p>
        <p className="mt-1">Добавлено {status.result.added} · обновлено {status.result.updated} · перенесено в архив {status.result.archived}</p>
      </div>}
      {status.webhookUrl && <div className="border-t border-[#dfece9] pt-4">
        <button type="button" className="text-sm font-semibold text-[#2e8175] underline" aria-expanded={showWebhook} onClick={() => setShowWebhook(!showWebhook)}>
          {showWebhook ? 'Скрыть адрес уведомлений' : 'Показать адрес уведомлений'}
        </button>
        {showWebhook && <label className="mt-2 block text-sm">
          Адрес для уведомлений об остатках FBO
          <input className="mt-1 w-full rounded-xl border border-[#dfece9] px-3 py-2" readOnly value={status.webhookUrl} onFocus={(event) => event.target.select()} />
        </label>}
      </div>}
    </>}
  </Card>;
}
