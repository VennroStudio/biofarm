import { MessageCircle } from 'lucide-react';
import { Card, Field, textareaClass } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

export function BitrixSettingsCard({ settings, onChange }: Props) {
  const set = (key: keyof Settings, value: Settings[keyof Settings]) => onChange({ ...settings, [key]: value });

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
    </Card>
  );
}
