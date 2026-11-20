export const cookieVersion = '2026-10-04';
export type CookieChoice = { version: string; analytics: boolean; marketing: boolean; expiresAt: number };
export function readCookieChoice(raw: string): CookieChoice | null {
  try {
    const value: unknown = JSON.parse(raw);
    if (typeof value !== 'object' || value === null) return null;
    const c = value as CookieChoice;
    return c.version === cookieVersion && typeof c.analytics === 'boolean' && typeof c.marketing === 'boolean' && typeof c.expiresAt === 'number' && c.expiresAt > Date.now() ? c : null;
  } catch { return null; }
}
