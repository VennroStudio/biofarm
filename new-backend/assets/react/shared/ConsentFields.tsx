export const consentVersion = '2026-10-04';
export type FormConsents = { personalDataConsent: boolean; marketingEmailConsent: boolean; offerAccepted?: boolean; consentVersion: string };
export function formConsents(form: HTMLFormElement): FormConsents {
  const data = new FormData(form);
  return { personalDataConsent: data.get('personalDataConsent') === 'on', marketingEmailConsent: data.get('marketingEmailConsent') === 'on', offerAccepted: data.get('offerAccepted') === 'on', consentVersion };
}
export function ConsentFields({ offer = false }: { offer?: boolean }) {
  return <div className="space-y-3 rounded-xl border border-border bg-background p-4 text-sm">
    <label className="flex items-start gap-3"><input className="mt-1 h-4 w-4 shrink-0 accent-primary" type="checkbox" name="personalDataConsent" required />
      <span>Я даю <a className="text-primary underline" href="/personal-data-consent" target="_blank" rel="noreferrer">согласие на обработку персональных данных</a> *</span></label>
    {offer && <label className="flex items-start gap-3"><input className="mt-1 h-4 w-4 shrink-0 accent-primary" type="checkbox" name="offerAccepted" required /><span>Принимаю <a className="text-primary underline" href="/oferta" target="_blank" rel="noreferrer">публичную оферту</a> *</span></label>}
    <label className="flex items-start gap-3"><input className="mt-1 h-4 w-4 shrink-0 accent-primary" type="checkbox" name="marketingEmailConsent" /><span>Хочу получать акции и предложения по e-mail. <a className="text-primary underline" href="/marketing-consent" target="_blank" rel="noreferrer">Согласие на рекламу</a></span></label>
    <p className="text-xs text-muted-foreground"><a className="underline" href="/privacy" target="_blank" rel="noreferrer">Политика обработки персональных данных</a>. Рекламное согласие необязательно.</p>
    {offer && <p className="text-xs text-muted-foreground"><a className="underline" href="/vozvrat" target="_blank" rel="noreferrer">Возврат и обмен</a> · <a className="underline" href="/bonus-rules" target="_blank" rel="noreferrer">Правила бонусной программы</a></p>}
  </div>;
}
