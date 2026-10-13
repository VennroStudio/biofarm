import { cookieVersion, readCookieChoice, type CookieChoice } from './cookiePolicy';
import { request } from './api';
export function mountCookieConsent() {
  const dialog = document.querySelector<HTMLDialogElement>('[data-cookie-dialog]');
  if (!dialog) return;
  const analytics = dialog.querySelector<HTMLInputElement>('[name="analytics"]')!;
  const marketing = dialog.querySelector<HTMLInputElement>('[name="marketing"]')!;
  const error = dialog.querySelector<HTMLElement>('[data-cookie-error]')!;
  let rawChoice = '';
  try { rawChoice = decodeURIComponent(document.cookie.split('; ').find(v => v.startsWith('biofarm_cookie_consent='))?.split('=').slice(1).join('=') || ''); } catch { /* Invalid cookie: ask again. */ }
  let choice = readCookieChoice(rawChoice);
  const activate = (c: CookieChoice) => {
    document.querySelectorAll<HTMLTemplateElement>('template[data-cookie-category]').forEach(template => {
      if (!(template.dataset.cookieCategory === 'analytics' ? c.analytics : c.marketing) || template.dataset.loaded) return;
      template.dataset.loaded = 'true';
      const fragment = template.content.cloneNode(true) as DocumentFragment;
      fragment.querySelectorAll('script').forEach(old => { const script = document.createElement('script'); for (const attr of old.attributes) script.setAttribute(attr.name, attr.value); script.textContent = old.textContent; old.replaceWith(script); });
      document.body.append(fragment);
    });
  };
  if (choice) activate(choice); else dialog.showModal();
  const open = () => { analytics.checked = choice?.analytics ?? false; marketing.checked = choice?.marketing ?? false; dialog.showModal(); };
  document.querySelectorAll('[data-cookie-settings]').forEach(button => button.addEventListener('click', open));
  dialog.addEventListener('cancel', event => event.preventDefault());
  dialog.querySelectorAll<HTMLButtonElement>('[data-cookie-choice]').forEach(button => button.addEventListener('click', () => { void save(button.dataset.cookieChoice!); }));
  async function save(mode: string) {
    const buttons = [...dialog!.querySelectorAll<HTMLButtonElement>('button')];
    buttons.forEach(button => button.disabled = true); error.textContent = '';
    const next = { version: cookieVersion, analytics: mode === 'all' || (mode === 'selected' && analytics.checked), marketing: mode === 'all' || (mode === 'selected' && marketing.checked), expiresAt: Date.now() + 180 * 86400000 };
    try {
      await request('/v1/privacy/cookies', { method: 'POST', body: next });
      document.cookie = `biofarm_cookie_consent=${encodeURIComponent(JSON.stringify(next))}; Path=/; Max-Age=15552000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
      const revoked = choice && ((choice.analytics && !next.analytics) || (choice.marketing && !next.marketing));
      choice = next; dialog!.close();
      if (revoked) { location.reload(); return; }
      activate(next);
    } catch { error.textContent = 'Не удалось сохранить выбор. Повторите попытку.'; }
    finally { buttons.forEach(button => button.disabled = false); }
  }
}
