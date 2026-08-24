import { Lock } from 'lucide-react';
import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, Card, Field, inputClass } from '../../../shared/ui';

export type PasswordForm = {
  current: string;
  next: string;
  confirm: string;
};

type Props = {
  password: PasswordForm;
  error: string | null;
  saved: boolean;
  saving: boolean;
  setPassword: Dispatch<SetStateAction<PasswordForm>>;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function PasswordSettingsCard({ password, error, saved, saving, setPassword, onSubmit }: Props) {
  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <Lock className="h-5 w-5" />
          Смена пароля
        </h2>
        <p className="text-sm text-[#789083]">Измените пароль для входа в админ-панель</p>
      </div>
      <form className="space-y-4" onSubmit={onSubmit}>
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
        <div className="flex flex-wrap items-center gap-3">
          <Button type="submit" variant="outline" disabled={saving}>
            <Lock className="h-4 w-4" />
            {saving ? 'Смена пароля...' : 'Изменить пароль'}
          </Button>
          {saved && <span className="text-sm font-semibold text-[#2f7d4b]">Пароль изменён</span>}
          {error && <span className="text-sm font-semibold text-[#c44747]">{error}</span>}
        </div>
      </form>
    </Card>
  );
}
