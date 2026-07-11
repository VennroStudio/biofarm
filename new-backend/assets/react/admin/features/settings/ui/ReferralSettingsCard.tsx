import { Gift, Percent } from 'lucide-react';
import { Card, Field, inputClass } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

export function ReferralSettingsCard({ settings, onChange }: Props) {
  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <Gift className="h-5 w-5" />
          Реферальная программа
        </h2>
        <p className="text-sm text-[#789083]">Настройки реферальной системы для партнёров</p>
      </div>
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <Field
            label={(
              <span className="flex items-center gap-2">
                <Percent className="h-4 w-4" />
                Процент рефералов
              </span>
            )}
          >
            <div className="relative">
              <input
                className={`${inputClass} pr-8`}
                type="number"
                min="0"
                max="100"
                value={settings.referral_percent}
                onChange={(event) => onChange({ ...settings, referral_percent: Number(event.target.value) })}
              />
              <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#789083]">%</span>
            </div>
          </Field>
          <p className="mt-2 text-xs text-[#789083]">Процент от покупок приглашённых пользователей</p>
        </div>
      </div>
    </Card>
  );
}
