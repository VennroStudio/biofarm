import { ArrowRight, Eye, EyeOff, Lock, Mail, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, useMemo, useState } from 'react';
import { login, register } from '../../site/api';
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label, cn } from '../../site/ui';

function redirectAfterLogin() {
  const params = new URLSearchParams(window.location.search);
  const redirect = params.get('redirect');

  window.location.href = redirect && redirect.startsWith('/') ? redirect : '/profile';
}

function LoginPage({ registrationEnabled }: { registrationEnabled: boolean }) {
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [registerName, setRegisterName] = useState('');
  const [registerEmail, setRegisterEmail] = useState('');
  const [registerPassword, setRegisterPassword] = useState('');
  const [registerConfirm, setRegisterConfirm] = useState('');

  const cardDescription = useMemo(
    () => (registrationEnabled ? 'Войдите или создайте аккаунт' : 'Войдите в свой аккаунт'),
    [registrationEnabled],
  );

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsLoading(true);
    setError('');
    setMessage('');

    try {
      await login(loginEmail, loginPassword);
      redirectAfterLogin();
    } catch (loginError) {
      setError(loginError instanceof Error ? loginError.message : 'Проверьте email и пароль');
    } finally {
      setIsLoading(false);
    }
  }

  async function handleRegister(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    setMessage('');

    if (registerPassword !== registerConfirm) {
      setError('Пароли не совпадают');
      return;
    }

    setIsLoading(true);
    try {
      const referralCode = window.localStorage.getItem('referralCode') || undefined;
      await register(registerEmail, registerPassword, registerName, referralCode);
      if (referralCode) {
        window.localStorage.removeItem('referralCode');
      }
      setMode('login');
      setMessage('Регистрация успешна. Проверьте почту и подтвердите аккаунт перед входом.');
    } catch (registerError) {
      setError(registerError instanceof Error ? registerError.message : 'Попробуйте другой email');
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <section className="min-h-screen bg-secondary/30 py-12 pt-28 md:py-20 md:pt-32">
      <div className="container mx-auto max-w-md px-4">
        <Card className="border-0 shadow-premium-lg">
          <CardHeader className="pb-2 text-center">
            <CardTitle className="text-2xl font-bold">Личный кабинет</CardTitle>
            <CardDescription>{cardDescription}</CardDescription>
          </CardHeader>

          <CardContent>
            {registrationEnabled && (
              <div className="mb-6 grid w-full grid-cols-2 rounded-md bg-muted p-1">
                <button
                  className={cn('rounded-sm px-3 py-1.5 text-sm font-medium transition-colors', mode === 'login' && 'bg-background shadow-sm')}
                  type="button"
                  onClick={() => setMode('login')}
                >
                  Вход
                </button>
                <button
                  className={cn('rounded-sm px-3 py-1.5 text-sm font-medium transition-colors', mode === 'register' && 'bg-background shadow-sm')}
                  type="button"
                  onClick={() => setMode('register')}
                >
                  Регистрация
                </button>
              </div>
            )}

            {mode === 'login' && (
              <form className="space-y-4" onSubmit={handleLogin}>
                <div className="space-y-2">
                  <Label htmlFor="login-email">Email</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10"
                      id="login-email"
                      placeholder="your@email.com"
                      required
                      type="email"
                      value={loginEmail}
                      onChange={(event) => setLoginEmail(event.target.value)}
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="login-password">Пароль</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10 pr-10"
                      id="login-password"
                      placeholder="••••••••"
                      required
                      type={showPassword ? 'text' : 'password'}
                      value={loginPassword}
                      onChange={(event) => setLoginPassword(event.target.value)}
                    />
                    <button
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                </div>

                {message && <p className="rounded bg-green-50 p-2 text-sm text-green-700">{message}</p>}
                {error && <p className="rounded bg-destructive/10 p-2 text-sm text-destructive">{error}</p>}

                <Button className="w-full" disabled={isLoading} type="submit">
                  {isLoading ? 'Вход...' : 'Войти'}
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </form>
            )}

            {registrationEnabled && mode === 'register' && (
              <form className="space-y-4" onSubmit={handleRegister}>
                <div className="space-y-2">
                  <Label htmlFor="register-name">Имя</Label>
                  <div className="relative">
                    <User className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10"
                      id="register-name"
                      placeholder="Иван Иванов"
                      required
                      type="text"
                      value={registerName}
                      onChange={(event) => setRegisterName(event.target.value)}
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="register-email">Email</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10"
                      id="register-email"
                      placeholder="your@email.com"
                      required
                      type="email"
                      value={registerEmail}
                      onChange={(event) => setRegisterEmail(event.target.value)}
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="register-password">Пароль</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10 pr-10"
                      id="register-password"
                      minLength={8}
                      placeholder="••••••••"
                      required
                      type={showPassword ? 'text' : 'password'}
                      value={registerPassword}
                      onChange={(event) => setRegisterPassword(event.target.value)}
                    />
                    <button
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="register-confirm">Подтвердите пароль</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-10"
                      id="register-confirm"
                      placeholder="••••••••"
                      required
                      type="password"
                      value={registerConfirm}
                      onChange={(event) => setRegisterConfirm(event.target.value)}
                    />
                  </div>
                </div>

                {error && <p className="rounded bg-destructive/10 p-2 text-sm text-destructive">{error}</p>}

                <Button className="w-full" disabled={isLoading} type="submit">
                  {isLoading ? 'Регистрация...' : 'Зарегистрироваться'}
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </form>
            )}

            <div className="mt-6 text-center text-sm text-muted-foreground">
              <p>
                Продолжая, вы соглашаетесь с{' '}
                <a className="text-primary hover:underline" href="/privacy">политикой конфиденциальности</a>, даете{' '}
                <a className="text-primary hover:underline" href="/personal-data-consent">согласие на обработку персональных данных</a>{' '}
                и принимаете условия{' '}
                <a className="text-primary hover:underline" href="/oferta">публичной оферты</a>.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </section>
  );
}

export function mountLoginPage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="login-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') {
      return;
    }
    root.dataset.mounted = 'true';
    createRoot(root).render(<LoginPage registrationEnabled={root.dataset.registrationEnabled === 'true'} />);
  });
}
