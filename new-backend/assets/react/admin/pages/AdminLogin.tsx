import { Lock, Mail } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { login } from '../api/client';
import { messageFromError } from '../shared/lib';
import { Button, Card, ErrorAlert, Field, inputClass } from '../shared/ui';

export function AdminLogin() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const adminRoot = document.getElementById('admin-root');
  const brandName = adminRoot?.dataset.brandName || 'БИОФАРМ';
  const brandLogoUrl = adminRoot?.dataset.brandLogoUrl || '/uploads/images/logo.png';

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      await login(email, password);
      navigate('/admin');
    } catch (reason) {
      setError(messageFromError(reason, 'Не удалось войти'));
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-[#eaf5f1] p-4">
      <Card className="w-full max-w-md p-6 shadow-[0_24px_70px_rgba(41,69,85,0.12)] sm:p-8">
        <div className="mb-6 text-center">
          <img src={brandLogoUrl} alt={brandName} className="mx-auto mb-5 h-12 w-auto max-w-[220px] object-contain [filter:brightness(0)_saturate(100%)_invert(29%)_sepia(20%)_saturate(1250%)_hue-rotate(122deg)_brightness(89%)_contrast(91%)]" />
          <h1 className="text-2xl font-semibold text-[#2e8175]">Админ-панель</h1>
          <p className="mt-1 text-sm text-[#5f7580]">Войдите для управления магазином</p>
        </div>
        <form className="grid gap-4" onSubmit={(event) => void submit(event)}>
          <Field label="Email">
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#5f7580]" />
              <input
                className={`${inputClass} pl-10`}
                name="email"
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                autoComplete="email"
                placeholder="admin@biofarm.ru"
                required
              />
            </div>
          </Field>
          <Field label="Пароль">
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#5f7580]" />
              <input
                className={`${inputClass} pl-10`}
                name="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                required
              />
            </div>
          </Field>
          <ErrorAlert>{error}</ErrorAlert>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Входим...' : 'Войти'}
          </Button>
        </form>
      </Card>
    </div>
  );
}
