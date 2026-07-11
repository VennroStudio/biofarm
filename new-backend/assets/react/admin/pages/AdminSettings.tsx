import { Gift, Lock, Percent, Power, Save, ShoppingBag } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { settingsApi } from '../api/resources';
import { Button, Card, Field, inputClass, PageHeader } from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { Settings } from '../types';

const defaults: Settings = {
  referral_percent: 5,
  order_bonus_enabled: true,
  order_bonus_percent: 5,
};

export function AdminSettings() {
  const [settings, setSettings] = useState<Settings>(defaults);
  const [saved, setSaved] = useState(false);
  const [saving, setSaving] = useState(false);
  const [password, setPassword] = useState({
    current: '',
    next: '',
    confirm: '',
  });

  useLoadOnMount(async () => {
    setSettings(await settingsApi.get());
  });

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      await settingsApi.update(settings);
      setSaved(true);
      window.setTimeout(() => setSaved(false), 1800);
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <PageHeader title="Настройки" subtitle="Конфигурация магазина и бонусной программы" />

      <form className="space-y-6" onSubmit={(event) => void submit(event)}>
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
                    onChange={(event) => setSettings({ ...settings, referral_percent: Number(event.target.value) })}
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#789083]">%</span>
                </div>
              </Field>
              <p className="mt-2 text-xs text-[#789083]">Процент от покупок приглашённых пользователей</p>
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div className="mb-6">
            <h2 className="flex items-center gap-2 text-2xl font-bold">
              <ShoppingBag className="h-5 w-5" />
              Бонусы за заказ
            </h2>
            <p className="text-sm text-[#789083]">Настройки начисления бонусов за покупки</p>
          </div>
          <div className="flex items-center justify-between rounded-lg border border-[#e4e5da] px-4 py-4">
            <div>
              <p className="flex items-center gap-2 font-semibold">
                <Power className="h-4 w-4" />
                Начисление бонусов за заказ
              </p>
              <p className="text-sm text-[#789083]">Пользователи получают бонусы за каждый заказ</p>
            </div>
            <button
              type="button"
              className={`relative h-7 w-12 rounded-full transition ${settings.order_bonus_enabled ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
              onClick={() => setSettings({ ...settings, order_bonus_enabled: !settings.order_bonus_enabled })}
              aria-label="Переключить бонусы за заказ"
            >
              <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings.order_bonus_enabled ? 'left-6' : 'left-1'}`} />
            </button>
          </div>
          {settings.order_bonus_enabled && (
            <div className="mt-5 max-w-xs">
              <Field
                label={(
                  <span className="flex items-center gap-2">
                    <Percent className="h-4 w-4" />
                    Процент бонусов за заказ
                  </span>
                )}
              >
                <div className="relative">
                  <input
                    className={`${inputClass} pr-8`}
                    type="number"
                    min="0"
                    max="100"
                    value={settings.order_bonus_percent}
                    onChange={(event) => setSettings({ ...settings, order_bonus_percent: Number(event.target.value) })}
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#789083]">%</span>
                </div>
              </Field>
            </div>
          )}
        </Card>

        <Card className="p-6">
          <div className="mb-6">
            <h2 className="flex items-center gap-2 text-2xl font-bold">
              <Lock className="h-5 w-5" />
              Смена пароля
            </h2>
            <p className="text-sm text-[#789083]">Измените пароль для входа в админ-панель</p>
          </div>
          <div className="space-y-4">
            <Field label="Текущий пароль">
              <input
                className={inputClass}
                type="password"
                value={password.current}
                onChange={(event) => setPassword({ ...password, current: event.target.value })}
                placeholder="Введите текущий пароль"
              />
            </Field>
            <Field label="Новый пароль">
              <input
                className={inputClass}
                type="password"
                value={password.next}
                onChange={(event) => setPassword({ ...password, next: event.target.value })}
                placeholder="Введите новый пароль (минимум 4 символа)"
              />
            </Field>
            <Field label="Подтвердите новый пароль">
              <input
                className={inputClass}
                type="password"
                value={password.confirm}
                onChange={(event) => setPassword({ ...password, confirm: event.target.value })}
                placeholder="Повторите новый пароль"
              />
            </Field>
            <Button type="button" variant="outline" disabled>
              <Lock className="h-4 w-4" />
              Изменить пароль
            </Button>
          </div>
        </Card>

        <div className="flex items-center justify-end gap-3">
          {saved && <span className="text-sm font-semibold text-[#2f7d4b]">Настройки сохранены</span>}
          <Button type="submit" disabled={saving}>
            <Save className="h-4 w-4" />
            {saving ? 'Сохранение...' : 'Сохранить все настройки'}
          </Button>
        </div>
      </form>
    </>
  );
}
