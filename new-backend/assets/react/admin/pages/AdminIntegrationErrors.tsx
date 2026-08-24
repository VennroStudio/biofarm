import { CheckCheck, CircleAlert } from 'lucide-react';
import { useMemo, useState } from 'react';
import { integrationErrorsApi } from '../api/resources';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader } from '../shared/ui';
import type { IntegrationErrorLog } from '../types';

const scenarioLabels: Record<string, string> = {
  admin_test: 'Проверка Bitrix24',
  feedback_form: 'Форма обратной связи',
  order_created: 'Создание заказа',
};

export function AdminIntegrationErrors() {
  const [errors, setErrors] = useState<IntegrationErrorLog[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const unreadCount = useMemo(() => errors.filter((item) => !item.is_read).length, [errors]);

  async function load() {
    setLoading(true);
    setError(null);
    try {
      const result = await integrationErrorsApi.list();
      setErrors(result.items);
    } catch (loadError) {
      setError(messageFromError(loadError, 'Не удалось загрузить ошибки интеграций'));
    } finally {
      setLoading(false);
    }
  }

  useLoadOnMount(load);

  async function markRead(id: number) {
    setSaving(true);
    setError(null);
    try {
      await integrationErrorsApi.markRead(id);
      setErrors((items) => items.map((item) => (item.id === id ? { ...item, is_read: true } : item)));
    } catch (readError) {
      setError(messageFromError(readError, 'Не удалось отметить ошибку прочитанной'));
    } finally {
      setSaving(false);
    }
  }

  async function markAllRead() {
    setSaving(true);
    setError(null);
    try {
      await integrationErrorsApi.markAllRead();
      setErrors((items) => items.map((item) => ({ ...item, is_read: true })));
    } catch (readError) {
      setError(messageFromError(readError, 'Не удалось отметить ошибки прочитанными'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <PageHeader
        title="Ошибки интеграций"
        subtitle="Сбои сценариев почты, Bitrix24, форм и заказов"
        actions={
          <Button disabled={saving || unreadCount === 0} onClick={() => void markAllRead()}>
            <CheckCheck className="h-4 w-4" />
            Прочитать все
          </Button>
        }
      />

      <ErrorAlert className="mb-5">{error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-6 flex flex-wrap items-center gap-3">
          <Badge tone={unreadCount > 0 ? 'red' : 'green'}>{unreadCount} новых</Badge>
          <Badge tone="gray">{errors.length} всего</Badge>
        </div>

        {loading && <p className="text-[#789083]">Загрузка...</p>}

        {!loading && errors.length === 0 && (
          <div className="rounded-md border border-dashed border-[#d9dece] px-4 py-10 text-center text-[#789083]">
            Ошибок интеграций пока нет
          </div>
        )}

        {!loading && errors.length > 0 && (
          <div className="grid gap-3">
            {errors.map((item) => (
              <article key={item.id} className="rounded-md border border-[#e4e5da] bg-[#fbfaf4] p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="mb-2 flex flex-wrap items-center gap-2">
                      {!item.is_read && <Badge tone="red">новая</Badge>}
                      <Badge tone="gray">{item.service}</Badge>
                      <Badge tone="blue">{scenarioLabels[item.scenario] || item.scenario}</Badge>
                      {item.http_status && <Badge tone="amber">HTTP {item.http_status}</Badge>}
                    </div>
                    <h2 className="break-words text-base font-bold text-[#1f3328]">{item.operation}</h2>
                    <p className="mt-1 break-words text-sm text-[#53685c]">{item.message}</p>
                  </div>
                  {!item.is_read && (
                    <Button size="sm" variant="outline" disabled={saving} onClick={() => void markRead(item.id)}>
                      Прочитано
                    </Button>
                  )}
                </div>
                <div className="mt-3 grid gap-1 text-xs text-[#789083] md:grid-cols-3">
                  <span>{item.created_at}</span>
                  <span>{item.local_entity_type || 'сущность'}: {item.local_entity_id || '-'}</span>
                  <span className="inline-flex items-center gap-1"><CircleAlert className="h-3.5 w-3.5" />{item.scenario}</span>
                </div>
                {item.response_body && (
                  <details className="mt-3">
                    <summary className="cursor-pointer text-sm font-semibold text-[#2f7d4b]">Ответ сервиса</summary>
                    <pre className="mt-2 max-h-56 overflow-auto rounded-md bg-white p-3 text-xs text-[#53685c]">{item.response_body}</pre>
                  </details>
                )}
              </article>
            ))}
          </div>
        )}
      </Card>
    </>
  );
}
