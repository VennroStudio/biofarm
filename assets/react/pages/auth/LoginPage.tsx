import { ConsentFields, formConsents } from '../../shared/ConsentFields';
import { ArrowRight, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, useMemo, useRef, useState } from 'react';
import { completeCheckoutPhone, login } from '../../site/api';
import { normalizePhone, PhoneVerification, smsUnavailableMessage } from '../../shared/PhoneVerification';
import { Button, Card, CardContent, CardDescription, CardHeader, Input, Label, cn } from '../../site/ui';
import { PhoneRegistration } from './PhoneRegistration';
import { PasswordSetupPage } from './PasswordSetupPage';

function redirectAfterLogin() {
  const params = new URLSearchParams(window.location.search);
  const redirect = params.get('redirect');

  const destination = redirect?.startsWith('/') ? new URL(redirect, window.location.origin) : null;
  window.location.href = destination?.origin === window.location.origin ? destination.href : '/profile';
}

function LoginPage({ registrationEnabled, phoneLoginEnabled, smsAvailable }: { registrationEnabled: boolean; phoneLoginEnabled: boolean; smsAvailable: boolean }) {
  const [mode, setMode] = useState<'login' | 'register'>(() =>
    registrationEnabled && new URLSearchParams(window.location.search).get('tab') === 'register' ? 'register' : 'login',
  );
  const [showPassword, setShowPassword] = useState(false);
  const [loginByPhone, setLoginByPhone] = useState(false);
  const [loginPhone, setLoginPhone] = useState('');
  const [phoneConsentRequired, setPhoneConsentRequired] = useState(false);
  const phoneForm = useRef<HTMLFormElement>(null);
  const normalizedPhone = normalizePhone(loginPhone);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  const cardDescription = useMemo(
    () => (registrationEnabled ? 'Войдите или создайте аккаунт' : 'Войдите в свой аккаунт'),
    [registrationEnabled],
  );

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsLoading(true);
    setError('');

    try {
      await login(loginEmail, loginPassword);
      redirectAfterLogin();
    } catch (loginError) {
      setError(loginError instanceof Error ? loginError.message : 'Проверьте email и пароль');
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <section className="bg-secondary/30 pb-12 pt-[128px] md:pb-16">
      <div className="container mx-auto px-4">
        <Card>
          <CardHeader className="pb-2 text-center">
            <h1 className="text-3xl font-normal tracking-tight text-primary">Личный кабинет</h1>
            <CardDescription>{cardDescription}</CardDescription>
          </CardHeader>

          <CardContent className="mx-auto w-full max-w-xl">
            {mode === 'login' && phoneLoginEnabled && (
              <div role="group" aria-label="Способ входа" className="mb-6 grid w-full grid-cols-2 rounded-xl border border-border bg-secondary p-1">
                <button
                  aria-pressed={!loginByPhone}
                  className={cn('rounded-sm px-3 py-1.5 text-sm font-medium transition-colors', !loginByPhone && 'bg-background shadow-sm')}
                  type="button"
                  onClick={() => setLoginByPhone(false)}
                >
                  Email
                </button>
                <button
                  aria-pressed={loginByPhone}
                  className={cn('rounded-sm px-3 py-1.5 text-sm font-medium transition-colors', loginByPhone && 'bg-background shadow-sm')}
                  type="button"
                  onClick={() => setLoginByPhone(true)}
                >
                  Номер
                </button>
              </div>
            )}

            {mode === 'login' && loginByPhone && !smsAvailable && (
              <p role="status" className="text-sm text-muted-foreground">{smsUnavailableMessage} Вы можете войти по email.</p>
            )}
            {mode === 'login' && loginByPhone && smsAvailable && (
              <form className="space-y-4" ref={phoneForm} onSubmit={event => event.preventDefault()}>
                <div className="space-y-2">
                  <Label htmlFor="login-phone">Телефон</Label>
                  <Input id="login-phone" type="tel" autoComplete="tel" placeholder="+7 (999) 123-45-67" value={loginPhone} onChange={event => {
                    setLoginPhone(event.target.value);
                    if (normalizePhone(event.target.value) !== normalizedPhone) setPhoneConsentRequired(false);
                  }} />
                </div>
                {phoneConsentRequired && <ConsentFields />}
                <PhoneVerification key={normalizedPhone ?? 'empty'} phone={normalizedPhone} verified={false}
                  showAccountNotice={false} endpoint="/v1/phone-login" disabled={false}
                  getConsents={() => formConsents(phoneForm.current!)}
                  onConsentRequired={() => setPhoneConsentRequired(true)}
                  onConfirm={async challenge => {
                    const user = await completeCheckoutPhone(challenge);
                    if (!user) throw new Error('Не удалось загрузить аккаунт. Попробуйте ещё раз.');
                    redirectAfterLogin();
                  }} />
              </form>
            )}
            {mode === 'login' && !loginByPhone && (
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
                      aria-label={showPassword ? 'Скрыть пароль' : 'Показать пароль'}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                </div>

                {error && <p className="rounded bg-destructive/10 p-2 text-sm text-destructive">{error}</p>}

                <Button className="w-full" disabled={isLoading} type="submit">
                  {isLoading ? 'Вход...' : 'Войти'}
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </form>
            )}

            {registrationEnabled && mode === 'register' && (
              phoneLoginEnabled && smsAvailable
                ? <PhoneRegistration onRegistered={redirectAfterLogin} />
                : <p role="status" className="text-sm text-muted-foreground">{
                  phoneLoginEnabled ? smsUnavailableMessage : 'Регистрация по номеру телефона временно недоступна.'
                }</p>
            )}

            {registrationEnabled && (
              <Button className="mt-4 w-full" variant="outline" type="button" onClick={() => {
                setError('');
                setMode(mode === 'login' ? 'register' : 'login');
              }}>
                {mode === 'login' ? 'Регистрация' : 'Вернуться ко входу'}
              </Button>
            )}

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
    createRoot(root).render(root.dataset.passwordSetup === 'true'
      ? <PasswordSetupPage />
      : <LoginPage registrationEnabled={root.dataset.registrationEnabled === 'true'}
        phoneLoginEnabled={root.dataset.phoneLoginEnabled === 'true'} smsAvailable={root.dataset.smsAvailable === 'true'} />);
  });
}
