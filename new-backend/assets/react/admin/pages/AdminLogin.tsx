import { Lock, Mail, Shield } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { login } from '../api/client';
import { Button, Card, Field, inputClass } from '../shared/ui';

export function AdminLogin() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      await login(email, password);
      navigate('/admin');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Не удалось войти');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#2f7d4b]/10 to-[#e5a11a]/10 p-4">
      <Card className="w-full max-w-md border-0 p-6 shadow-[0_18px_55px_rgba(31,51,40,0.16)]">
        <div className="mb-6 text-center">
          <span className="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-[#2f7d4b]/10 text-[#2f7d4b]">
            <Shield className="h-8 w-8" />
          </span>
          <h1 className="text-2xl font-bold text-[#26382d]">Админ-панель</h1>
          <p className="mt-1 text-sm text-[#789083]">Войдите для управления магазином</p>
        </div>
        <form className="grid gap-4" onSubmit={(event) => void submit(event)}>
          <Field label="Email">
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
              <input
                className={`${inputClass} pl-10`}
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                autoComplete="email"
                placeholder="admin@biofarm.ru"
              />
            </div>
          </Field>
          <Field label="Пароль">
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
              <input
                className={`${inputClass} pl-10`}
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
              />
            </div>
          </Field>
          {error && <p className="rounded-md bg-[#f7e2e2] px-3 py-2 text-sm font-semibold text-[#a33d3d]">{error}</p>}
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Входим...' : 'Войти'}
          </Button>
        </form>
      </Card>
    </div>
  );
}
