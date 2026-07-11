import { Lock } from 'lucide-react';
import type { Dispatch, SetStateAction } from 'react';
import { Button, Card, Field, inputClass } from '../../../shared/ui';

export type PasswordForm = {
  current: string;
  next: string;
  confirm: string;
};

type Props = {
  password: PasswordForm;
  setPassword: Dispatch<SetStateAction<PasswordForm>>;
};

export function PasswordSettingsCard({ password, setPassword }: Props) {
  return (
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
  );
}
