import { type FormEvent, useState } from 'react';
import { savePasswordFromLink } from '../../site/api';
import { Button, Card, CardContent, CardHeader, Input, Label } from '../../site/ui';

export function PasswordSetupPage() {
  const token = new URLSearchParams(window.location.search).get('token') ?? '';
  const [busy, setBusy] = useState(false);
  const [visible, setVisible] = useState(false);
  const [error, setError] = useState('');

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      const password = String(new FormData(event.currentTarget).get('password') ?? '');
      await savePasswordFromLink(token, password);
      window.location.replace('/login');
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : 'Не удалось сохранить пароль.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="bg-secondary/30 px-4 pb-12 pt-[128px]">
      <Card className="mx-auto w-full max-w-xl">
        <CardHeader><h1 className="text-3xl font-normal text-primary">Задать пароль</h1></CardHeader>
        <CardContent>
          {token ? (
            <form className="space-y-4" onSubmit={submit}>
              <div className="space-y-2">
                <Label htmlFor="new-password">Новый пароль</Label>
                <Input autoComplete="new-password" id="new-password" name="password" minLength={8} maxLength={64}
                  type={visible ? 'text' : 'password'} aria-describedby="new-password-hint" required />
                <button type="button" className="text-sm text-primary underline" onClick={() => setVisible(value => !value)}>
                  {visible ? 'Скрыть пароль' : 'Показать пароль'}
                </button>
                <p id="new-password-hint" className="text-sm text-muted-foreground">От 8 символов, с заглавной и строчной латинскими буквами, цифрой и специальным символом.</p>
              </div>
              {error && <p role="alert" className="text-sm text-destructive">{error}</p>}
              <Button className="w-full" type="submit" disabled={busy}>{busy ? 'Сохраняем…' : 'Сохранить пароль'}</Button>
            </form>
          ) : <p className="text-sm text-muted-foreground">Откройте ссылку из письма, чтобы задать пароль.</p>}
        </CardContent>
      </Card>
    </section>
  );
}
