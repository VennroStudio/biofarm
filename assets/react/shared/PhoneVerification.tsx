import { useEffect, useEffectEvent, useState } from 'react';
import { request, SiteRequestError } from '../site/api';
import { Button, Input, Label } from '../site/ui';
import type { FormConsents } from './ConsentFields';

type VerificationState = 'pending' | 'code' | 'confirmed' | 'failed';
type Challenge = { challenge: string; state: VerificationState; resendAfter: number };
export const smsUnavailableMessage = 'Подтверждение телефона по СМС временно недоступно.';

export function normalizePhone(value: string) {
  if (!/^\+?[0-9 ().-]{10,30}$/.test(value.trim())) return null;
  const digits = value.replace(/[^0-9]/g, '');
  if (/^9[0-9]{9}$/.test(digits)) return '7' + digits;
  return /^[78]9[0-9]{9}$/.test(digits) ? '7' + digits.slice(1) : null;
}

export function PhoneVerification({ phone, verified, available = true, showAccountNotice, getConsents, onConfirm, onConsentRequired, disabled, endpoint }: {
  phone: string | null;
  verified: boolean;
  available?: boolean;
  showAccountNotice: boolean;
  endpoint: string;
  getConsents: () => FormConsents;
  onConfirm: (challenge: string) => Promise<void>;
  onConsentRequired?: () => void;
  disabled: boolean;
}) {
  const [challenge, setChallenge] = useState<Challenge | null>(null);
  const [code, setCode] = useState('');
  const [busy, setBusy] = useState(false);
  const [waiting, setWaiting] = useState(false);
  const [resendReady, setResendReady] = useState(true);
  const [error, setError] = useState('');

  const token = challenge?.challenge;
  const state = challenge?.state;

  function message(cause: unknown) {
    setError(cause instanceof Error ? cause.message : 'Не удалось подтвердить номер. Попробуйте ещё раз.');
  }

  async function complete(value: string) {
    setBusy(true);
    setError('');
    try {
      await onConfirm(value);
    } catch (cause) {
      message(cause);
    } finally {
      setBusy(false);
    }
  }

  const completeAfterPolling = useEffectEvent(complete);

  useEffect(() => {
    if (!token) return;
    const timer = window.setTimeout(() => setResendReady(true), (challenge?.resendAfter ?? 60) * 1000);
    return () => window.clearTimeout(timer);
  }, [token, challenge?.resendAfter]);

  useEffect(() => {
    if (!token || verified || (state !== 'pending' && !waiting)) return;
    const controller = new AbortController();
    const timer = window.setInterval(() => {
      void request<{ state: VerificationState }>(`${endpoint}/verify`, {
        method: 'POST', body: { challenge: token }, signal: controller.signal,
      }).then(result => {
        if (controller.signal.aborted) return;
        setChallenge(current => current ? { ...current, state: result.state } : current);
        if (result.state === 'confirmed' || result.state === 'failed') {
          controller.abort();
          setWaiting(false);
          if (result.state === 'confirmed') void completeAfterPolling(token);
        }
      }).catch(cause => {
        if (!controller.signal.aborted) {
          controller.abort();
          setWaiting(false);
          setChallenge(current => current ? { ...current, state: 'failed' } : current);
          setError(cause instanceof Error ? cause.message : 'Не удалось проверить код.');
        }
      });
    }, 3500);

    return () => {
      controller.abort();
      window.clearInterval(timer);
    };
  }, [endpoint, state, token, verified, waiting]);

  async function start() {
    setBusy(true);
    setWaiting(false);
    setError('');
    setCode('');
    try {
      const next = await request<Challenge>(`${endpoint}/start`, {
        method: 'POST', body: { phone, ...getConsents(), smsCodeRequested: true },
      });
      setChallenge(next);
      setResendReady(false);
      if (next.state === 'confirmed') await complete(next.challenge);
    } catch (cause) {
      if (cause instanceof SiteRequestError && cause.code === 1) onConsentRequired?.();
      message(cause);
    } finally {
      setBusy(false);
    }
  }

  async function verify() {
    if (!token) return;
    setBusy(true);
    setError('');
    try {
      const result = await request<{ state: VerificationState }>(`${endpoint}/verify`, {
        method: 'POST', body: { challenge: token, code },
      });
      setChallenge(current => current ? { ...current, state: result.state } : current);
      if (result.state === 'confirmed') await complete(token);
      else if (result.state !== 'failed') setWaiting(true);
    } catch (cause) {
      message(cause);
    } finally {
      setBusy(false);
    }
  }

  if (verified) return <p className="text-sm font-medium text-primary" role="status">Телефон подтверждён</p>;
  if (!available) return (
    <p className="text-sm text-muted-foreground" role="status">
      {smsUnavailableMessage}{' '}
      {showAccountNotice && <a className="underline" href={`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`}>
        Войдите по email, если уже подтверждали номер.
      </a>}
    </p>
  );

  const locked = busy || disabled;
  return (
    <div className="space-y-3 rounded-xl border border-border p-4">
      {showAccountNotice && (
        <p className="text-sm text-muted-foreground">
          Подтвердите телефон, чтобы войти или создать аккаунт. Если у вас уже есть аккаунт с непроверенным номером,{' '}
          <a className="underline" href={`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`}>войдите по email</a>.
        </p>
      )}
      {!challenge && <Button aria-describedby="phone-code-note" type="button" onClick={() => void start()} disabled={locked || !phone}>{busy ? 'Отправляем…' : 'Получить код'}</Button>}
      {(!challenge || state !== 'confirmed') && (
        <p id="phone-code-note" className="text-xs text-muted-foreground">
          Запрашивая код, вы соглашаетесь получить одноразовый код по SMS на указанный номер, если подтверждение через SIM-карту не пройдёт.
        </p>
      )}
      {(state === 'pending' || waiting) && <p className="text-sm text-muted-foreground" role="status">Ожидаем подтверждение телефона…</p>}
      {state === 'code' && (
        <div className="space-y-2">
          <Label htmlFor="checkout-phone-code">Код из SMS</Label>
          <div className="flex flex-wrap gap-2">
            <Input
              id="checkout-phone-code"
              className="w-28"
              inputMode="numeric"
              autoComplete="one-time-code"
              maxLength={4}
              value={code}
              disabled={locked || waiting}
              onChange={event => setCode(event.target.value.replace(/[^0-9]/g, ''))}
              onKeyDown={event => {
                if (event.key === 'Enter') {
                  event.preventDefault();
                  if (code.length === 4 && !locked && !waiting) void verify();
                }
              }}
            />
            <Button type="button" onClick={() => void verify()} disabled={locked || waiting || code.length !== 4}>Подтвердить код</Button>
          </div>
        </div>
      )}
      {state === 'confirmed' && token && (
        <Button type="button" onClick={() => void complete(token)} disabled={locked}>{busy ? 'Сохраняем…' : 'Продолжить'}</Button>
      )}
      {challenge && state !== 'confirmed' && (
        <Button aria-describedby="phone-code-note" type="button" variant="outline" onClick={() => void start()} disabled={locked || !resendReady}>Отправить повторно</Button>
      )}
      {error && <p className="text-sm text-destructive" role="alert">{error}</p>}
      {state === 'failed' && !error && <p className="text-sm text-destructive" role="alert">Номер не подтверждён. Запросите новый код.</p>}
    </div>
  );
}
