import { type FormEvent, useRef, useState } from 'react';
import { ConsentFields, formConsents } from '../../shared/ConsentFields';
import { normalizePhone, PhoneVerification } from '../../shared/PhoneVerification';
import { completePhoneRegistration, SiteRequestError } from '../../site/api';
import { registrationReferralCode } from '../../site/referral';
import { Button, Input, Label } from '../../site/ui';

export function PhoneRegistration({ onRegistered }: { onRegistered: () => void }) {
  const form = useRef<HTMLFormElement>(null);
  const [phone, setPhone] = useState('');
  const [consent, setConsent] = useState(false);
  const [challenge, setChallenge] = useState<string | null>(null);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const normalizedPhone = normalizePhone(phone);

  async function createAccount(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (challenge === null || busy) return;
    setBusy(true);
    setError('');
    try {
      const referral = await registrationReferralCode();
      const account = await completePhoneRegistration(challenge, name, email, referral);
      if (!account) throw new Error('Не удалось загрузить аккаунт. Попробуйте войти.');
      onRegistered();
    } catch (cause) {
      if (cause instanceof SiteRequestError && (cause.code === 74 || cause.code === 1)) {
        setChallenge(null);
        setConsent(false);
      }
      setError(cause instanceof Error ? cause.message : 'Не удалось создать аккаунт.');
    } finally {
      setBusy(false);
    }
  }

  if (challenge === null) {
    return (
      <form ref={form} className="space-y-4" onSubmit={event => event.preventDefault()}>
        <div className="space-y-2">
          <Label htmlFor="register-phone">Телефон</Label>
          <Input id="register-phone" name="phone" type="tel" autoComplete="tel" placeholder="+7 (999) 123-45-67"
            value={phone} onChange={event => { setPhone(event.target.value); setError(''); }} />
        </div>
        <ConsentFields onChange={setConsent} />
        <PhoneVerification key={normalizedPhone ?? 'empty'} phone={normalizedPhone} verified={false} showAccountNotice={false}
          endpoint="/v1/phone-registration" disabled={!consent} getConsents={() => formConsents(form.current!)}
          onConfirm={async value => {
            if (!form.current || normalizePhone(String(new FormData(form.current).get('phone') ?? '')) !== normalizedPhone) return;
            setChallenge(value);
            setError('');
          }} />
        {error && <p role="alert" className="text-sm text-destructive">{error}</p>}
      </form>
    );
  }

  return (
    <form className="space-y-4" onSubmit={createAccount}>
      <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
        <p role="status">Телефон подтверждён</p>
        <button className="text-primary underline" type="button" disabled={busy} onClick={() => {
          setChallenge(null);
          setConsent(false);
          setError('');
        }}>Изменить номер</button>
      </div>
      <div className="space-y-2">
        <Label htmlFor="register-name">Имя и фамилия</Label>
        <Input id="register-name" autoComplete="name" placeholder="Иван Иванов" required
          value={name} onChange={event => setName(event.target.value)} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="register-email">Email</Label>
        <Input id="register-email" type="email" autoComplete="email" placeholder="your@email.com" required
          value={email} onChange={event => setEmail(event.target.value)} />
      </div>
      {error && <p role="alert" className="text-sm text-destructive">{error}</p>}
      <Button className="w-full" disabled={busy} type="submit">{busy ? 'Создаём аккаунт…' : 'Создать аккаунт'}</Button>
    </form>
  );
}
