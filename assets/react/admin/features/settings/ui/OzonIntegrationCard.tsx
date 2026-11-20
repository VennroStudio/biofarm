import { Package, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { request } from '../../../api/client';
import { messageFromError } from '../../../shared/lib';
import { Badge, Button, Card, ErrorAlert } from '../../../shared/ui';

const endpoint = '/admin/api/integrations/ozon';

type Stock = { name: string; ozon_id: string; stock: number; fbs_stock: number; stock_checked_at: string | null; warehouses: { id: string; name: string; free: number }[] };

type SyncResult = {
  added: number;
  updated: number;
  archived: number;
  visible: number;
  available?: number;
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
  fbsConnected: boolean;
  queuedStocks: number;
  stockReconciledAt: string | null;
};

export function OzonIntegrationCard() {
  const [status, setStatus] = useState<OzonStatus | null>(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [showWebhook, setShowWebhook] = useState(false);
  const [stocks, setStocks] = useState<Stock[] | null>(null);
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

  async function importCatalog() {
    if (!status?.first || !window.confirm('Заменить текущий ассортимент товарами из Ozon? Старые карточки будут перенесены в архив.')) {
      return;
    }
    setBusy(true);
    setError('');
    try {
      setStatus(await request<OzonStatus>(`${endpoint}/sync`, { method: 'POST' }));
    } catch (reason) {
      setError(messageFromError(reason, 'Не удалось запустить импорт каталога Ozon.'));
    } finally {
      setBusy(false);
    }
  }

  return <Card className="space-y-4 p-6">
    <div>
      <h2 className="flex items-center gap-2 text-xl font-semibold"><Package className="h-5 w-5" aria-hidden="true" />Ozon Seller</h2>
      <p className="mt-1 text-sm text-[#5f7580]">Первичный импорт каталога и обновление остатков FBO и FBS.</p>
    </div>
    <ErrorAlert>{error || status?.error}</ErrorAlert>
    {status && <>
      <div className="flex flex-wrap items-center gap-3">
        <Badge tone={status.connected ? 'green' : 'gray'}>
          {status.connected ? 'Подключено' : status.configured ? 'Ожидает подключения' : 'Параметры не заданы'}
        </Badge>
        {status.lastSuccessAt && <span className="text-sm text-[#5f7580]">
          Импорт завершён {new Date(status.lastSuccessAt).toLocaleString('ru-RU')}
        </span>}
      </div>
      {status.connected && !status.first && <p className="text-sm">
        Последняя сверка остатков — {status.stockReconciledAt ? new Date(status.stockReconciledAt).toLocaleString('ru-RU') : 'ожидается'} · В очереди {status.queuedStocks}
      </p>}
      {status.connected && !status.fbsConnected && <p className="text-sm">Для отслеживания FBS нажмите «Переподключить Ozon» и подтвердите доступ к отправлениям.</p>}
      {status.deliveryEnabled && <p className="text-sm text-[#5f7580]">
        {status.deliveryReady ? 'Доставка в ПВЗ Ozon подключена.' : 'Для доставки в ПВЗ проверьте права Ozon, оплату, кассу и подтверждение телефона.'}
      </p>}
      {status.configured ? <p className="text-sm text-[#5f7580]">
        {status.first
          ? 'Импорт заменит текущий ассортимент товарами из Ozon. История заказов сохранится.'
          : 'Остатки обновляются по уведомлениям Ozon. Цены и карточки редактируются на сайте.'}
      </p> : <p className="text-sm text-[#5f7580]">Укажите параметры приложения Ozon Seller на сервере.</p>}
      {status.configured && !status.connected && <p className="break-all text-sm text-[#5f7580]">
        URL для OAuth в настройках приложения Ozon <code>{status.redirectUri}</code>
      </p>}
      <div className="flex flex-wrap gap-3">
        <Button variant="outline" disabled={busy || pending || !status.configured} onClick={() => void connect()}>
          {status.connected ? 'Переподключить Ozon' : 'Подключить Ozon'}
        </Button>
        {status.first && <Button disabled={busy || pending || !status.connected} onClick={() => void importCatalog()}>
          <RefreshCw className="h-4 w-4" aria-hidden="true" />
          {pending ? 'Импорт…' : 'Импортировать каталог'}
        </Button>}
      </div>
      {pending && <p role="status" className="text-sm text-[#5f7580]">Запрос принят. Результат появится после обработки.</p>}
      {status.result && <div role="status" className="rounded-xl bg-[#f4faf8] p-3 text-sm">
        <p>На момент импорта было {status.result.visible} товаров на витрине · {status.result.available !== undefined ? `в наличии ${status.result.available} · ` : ''}групп {status.result.groups}</p>
        <p className="mt-1">Добавлено {status.result.added} · обновлено {status.result.updated} · перенесено в архив {status.result.archived}</p>
      </div>}
      {status.connected && !status.first && <details onToggle={event => {
        if (event.currentTarget.open) void request<Stock[]>(`${endpoint}?view=stocks`).then(setStocks).catch(reason => setError(messageFromError(reason, 'Не удалось загрузить остатки.')));
      }}>
        <summary className="cursor-pointer font-medium">Остатки по складам</summary>
        <div className="max-h-96 overflow-auto">{stocks?.map(item => <div key={item.ozon_id} className="border-b py-2 text-sm">
          <strong>{item.name}</strong> · FBO {item.stock} · FBS {item.fbs_stock}
          {item.warehouses.map(w => <span key={w.id}> · {w.name || w.id} ({w.free})</span>)}
          {item.stock_checked_at && <span> · {new Date(item.stock_checked_at.replace(' ', 'T') + 'Z').toLocaleString('ru-RU')}</span>}
        </div>)}</div>
      </details>}
      {status.webhookUrl && <div className="border-t border-[#dfece9] pt-4">
        <button type="button" className="text-sm font-semibold text-[#2e8175] underline" aria-expanded={showWebhook} onClick={() => setShowWebhook(!showWebhook)}>
          {showWebhook ? 'Скрыть адрес уведомлений' : 'Показать адрес уведомлений'}
        </button>
        {showWebhook && <label className="mt-2 block text-sm">
          Адрес для уведомлений об остатках FBO и FBS
          <input className="mt-1 w-full rounded-xl border border-[#dfece9] px-3 py-2" readOnly value={status.webhookUrl} onFocus={(event) => event.target.select()} />
        </label>}
      </div>}
    </>}
  </Card>;
}
