import { useState } from 'react';
import { marketingVersion } from '../../../shared/ConsentFields';
import { updatePrivacyPreferences, type SiteUser } from '../../../site/api';
import { Button, Card, CardContent, CardHeader, CardTitle } from '../../../site/ui';

export function PrivacyPreferencesCard({ user, onChange }: { user: SiteUser; onChange: (user: SiteUser) => void }) {
  const [saving, setSaving] = useState(false);
  const [confirming, setConfirming] = useState(false);
  const [message, setMessage] = useState<{ text: string; error?: boolean }>();
  const subscribed = user.marketingEmailSubscribed === true;

  async function change(revokePersonalData: boolean) {
    setSaving(true);
    setMessage(undefined);
    try {
      const updated = await updatePrivacyPreferences(revokePersonalData
        ? { revokePersonalDataConsent: true }
        : { marketingEmailSubscribed: !subscribed, marketingVersion, email: user.email });
      onChange(updated);
      setConfirming(false);
      setMessage({ text: revokePersonalData ? 'Согласие отозвано.' : subscribed ? 'Вы отписались от рассылки.' : 'Вы подписались на рассылку.' });
    } catch (error) {
      setMessage({ text: error instanceof Error ? error.message : 'Не удалось сохранить настройки.', error: true });
    } finally {
      setSaving(false);
    }
  }

  return (
    <Card className="mt-6">
      <CardHeader>
        <CardTitle>Рассылка и согласия</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-1">
            <p className="font-medium">Акции и предложения по e-mail</p>
            <p className="text-sm text-muted-foreground">
              {subscribed ? 'Вы подписаны' : 'Вы не подписаны'} · {user.email}
            </p>
            <a className="text-sm text-primary underline" href="/marketing-consent" target="_blank" rel="noreferrer">Согласие на рекламу</a>
            {!subscribed && !user.emailVerified && <p className="text-sm text-muted-foreground">Для подписки подтвердите e-mail.</p>}
          </div>
          <Button variant="outline" type="button" disabled={saving || (!subscribed && !user.emailVerified)} onClick={() => void change(false)}>
            {subscribed ? 'Отписаться' : 'Подписаться'}
          </Button>
        </div>
        {user.personalDataConsentValid && (
          <div className="space-y-3 border-t border-border pt-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <a className="text-sm text-primary underline" href="/personal-data-consent" target="_blank" rel="noreferrer">Согласие на обработку персональных данных</a>
              {!confirming && <Button variant="outline" type="button" disabled={saving} onClick={() => setConfirming(true)}>Отозвать</Button>}
            </div>
            {confirming && (
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Если для нового заказа понадобится согласие, мы запросим его снова.</p>
                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" type="button" disabled={saving} onClick={() => void change(true)}>Подтвердить отзыв</Button>
                  <Button variant="ghost" type="button" disabled={saving} onClick={() => setConfirming(false)}>Отмена</Button>
                </div>
              </div>
            )}
          </div>
        )}
        {message && <p className={message.error ? 'text-sm text-destructive' : 'text-sm text-primary'} role={message.error ? 'alert' : 'status'}>{message.text}</p>}
      </CardContent>
    </Card>
  );
}
