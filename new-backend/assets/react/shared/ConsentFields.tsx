export const consentVersion = '2026-11-16';
export const offerVersion = '2026-11-16';
export const marketingVersion = '2026-10-04';
export const ozonTermsUrl = 'https://docs.ozon.ru/legal/terms-of-use/site/external-orders/';

export type FormConsents = { personalDataConsent: boolean; consentVersion: string };

export function formConsents(form: HTMLFormElement): FormConsents {
  const data = new FormData(form);
  return { personalDataConsent: data.get('personalDataConsent') === 'on', consentVersion };
}

export function ConsentFields({ onChange }: { onChange?: (accepted: boolean) => void }) {
  return <div className="space-y-2 rounded-xl border border-border bg-background p-4 text-sm">
    <label className="flex items-start gap-3">
      <input className="mt-1 h-4 w-4 shrink-0 accent-primary" type="checkbox" name="personalDataConsent" required
        onChange={onChange ? (event) => onChange(event.target.checked) : undefined} />
      <span>Я даю <a className="text-primary underline" href="/personal-data-consent" target="_blank" rel="noreferrer">согласие на обработку персональных данных</a></span>
    </label>
    <p className="text-xs text-muted-foreground"><a className="underline" href="/privacy" target="_blank" rel="noreferrer">Политика обработки персональных данных</a></p>
  </div>;
}
