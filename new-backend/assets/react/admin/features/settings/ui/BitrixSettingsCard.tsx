import { CheckCircle2, MessageCircle, PlugZap } from 'lucide-react';
import { useEffect, useState } from 'react';
import { bitrix24Api, settingsApi } from '../../../api/resources';
import { messageFromError } from '../../../shared/lib';
import { Button, Card, Field, inputClass, textareaClass } from '../../../shared/ui';
import type { Bitrix24IntegrationSettings, Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

export function BitrixSettingsCard({ settings, onChange }: Props) {
  const set = (key: keyof Settings, value: Settings[keyof Settings]) => onChange({ ...settings, [key]: value });
  const [crm, setCrm] = useState<Bitrix24IntegrationSettings | null>(null);
  const [crmEnabled, setCrmEnabled] = useState(false);
  const [webhookUrl, setWebhookUrl] = useState('');
  const [crmError, setCrmError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [crmTested, setCrmTested] = useState(false);
  const [saving, setSaving] = useState(false);
  const [testingCrm, setTestingCrm] = useState(false);

  useEffect(() => {
    let alive = true;

    bitrix24Api.get()
      .then((state) => {
        if (!alive) {
          return;
        }
        setCrm(state);
        setCrmEnabled(state.enabled);
      })
      .catch((error) => {
        if (alive) {
          setCrmError(messageFromError(error, 'Не удалось загрузить настройки CRM'));
        }
      });

    return () => {
      alive = false;
    };
  }, []);

  async function saveIntegrations() {
    setCrmError(null);
    setSaved(false);
    setCrmTested(false);
    setSaving(true);

    try {
      await settingsApi.update({
        bitrix_widget_enabled: settings.bitrix_widget_enabled,
        bitrix_widget_code: settings.bitrix_widget_code,
      });

      const next = await bitrix24Api.update({
        enabled: crmEnabled,
        webhook_url: webhookUrl.trim() || undefined,
      });
      setCrm(next);
      setCrmEnabled(next.enabled);
      set('bitrix_crm_enabled', next.enabled);
      setWebhookUrl('');
      setSaved(true);
      window.setTimeout(() => setSaved(false), 1800);
    } catch (error) {
      setCrmError(messageFromError(error, 'Не удалось сохранить интеграции'));
    } finally {
      setSaving(false);
    }
  }

  async function testCrm() {
    setCrmError(null);
    setCrmTested(false);
    setTestingCrm(true);

    try {
      await bitrix24Api.test();
      setCrmTested(true);
      window.setTimeout(() => setCrmTested(false), 1800);
    } catch (error) {
      setCrmError(messageFromError(error, 'Не удалось подключиться к Bitrix24'));
    } finally {
      setTestingCrm(false);
    }
  }

  return (
    <Card className="p-6">
      <div className="mb-5 flex items-start justify-between gap-4">
        <div>
          <h2 className="flex items-center gap-2 text-2xl font-bold">
            <MessageCircle className="h-5 w-5" />
            Bitrix24
          </h2>
          <p className="text-sm text-[#789083]">Виджет открытой линии, чата и CRM-формы на сайте.</p>
        </div>
        <button
          aria-label="Переключить виджет Bitrix24"
          aria-pressed={settings.bitrix_widget_enabled}
          className={`relative h-7 w-12 shrink-0 rounded-full transition ${settings.bitrix_widget_enabled ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
          type="button"
          onClick={() => set('bitrix_widget_enabled', !settings.bitrix_widget_enabled)}
        >
          <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.bitrix_widget_enabled ? 'left-6' : 'left-1'}`} />
        </button>
      </div>

      <Field label="Код виджета Bitrix24">
        <textarea
          className={`${textareaClass} min-h-40 font-mono text-sm`}
          value={settings.bitrix_widget_code}
          onChange={(event) => set('bitrix_widget_code', event.target.value)}
          placeholder="<script>...</script>"
        />
      </Field>
      <p className="mt-2 text-sm text-[#789083]">
        Код выводится перед закрывающим тегом body только если виджет включен.
      </p>

      <div className="mt-6 border-t border-[#edf0e8] pt-5">
        <div className="mb-4 flex items-start justify-between gap-4">
          <div>
            <h3 className="flex items-center gap-2 text-lg font-bold text-[#1f3328]">
              <PlugZap className="h-5 w-5" />
              CRM-сценарии
            </h3>
            <p className="text-sm text-[#789083]">Форма обратной связи и заказы остаются на сайте, а в Bitrix24 отправляется копия.</p>
          </div>
          <button
            aria-label="Переключить отправку в Bitrix24 CRM"
            aria-pressed={crmEnabled}
            className={`relative h-7 w-12 shrink-0 rounded-full transition ${crmEnabled ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
            type="button"
            onClick={() => setCrmEnabled((enabled) => !enabled)}
          >
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${crmEnabled ? 'left-6' : 'left-1'}`} />
          </button>
        </div>

        {crmError && (
          <div className="mb-4 rounded-md border border-[#f0c9c9] bg-[#fff5f5] px-4 py-3 text-sm font-semibold text-[#9f3b3b]">
            {crmError}
          </div>
        )}

        <Field label="Входящий вебхук Bitrix24">
          <input
            className={inputClass}
            type="password"
            autoComplete="off"
            value={webhookUrl}
            onChange={(event) => setWebhookUrl(event.target.value)}
            placeholder={crm?.has_webhook ? 'Оставьте пустым, чтобы не менять сохраненный вебхук' : 'https://bitrix24.ru/rest/'}
          />
        </Field>
        <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-[#789083]">
          {crm?.has_webhook ? <span>Сохранен: {crm.webhook_mask}</span> : <span>Вебхук еще не сохранен.</span>}
          {saved && <span className="inline-flex items-center gap-1 font-semibold text-[#2f7d4b]"><CheckCircle2 className="h-4 w-4" />Сохранено</span>}
          {crmTested && <span className="inline-flex items-center gap-1 font-semibold text-[#2f7d4b]"><CheckCircle2 className="h-4 w-4" />Связь есть</span>}
        </div>

        <div className="mt-4 flex flex-wrap gap-3">
          <Button disabled={saving} onClick={() => void saveIntegrations()}>
            {saving ? 'Сохранение...' : 'Сохранить'}
          </Button>
          <Button disabled={testingCrm || !crm?.has_webhook} variant="outline" onClick={() => void testCrm()}>
            {testingCrm ? 'Проверка...' : 'Проверить связь'}
          </Button>
        </div>
      </div>
    </Card>
  );
}
