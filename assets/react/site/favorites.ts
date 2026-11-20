import { addFavorite, getFavorites, getToken, removeFavorite } from './api';

const storageKey = 'biofarm_favorites_guest';

function notifyFavoritesUpdated() {
  window.dispatchEvent(new Event('biofarm-favorites-updated'));
}

function normalizeIds(value: unknown): number[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return Array.from(new Set(value.map((item) => Number(item)).filter((id) => Number.isFinite(id) && id > 0)));
}

export function readGuestFavorites(): number[] {
  const raw = window.localStorage.getItem(storageKey);
  if (!raw) {
    return [];
  }

  try {
    return normalizeIds(JSON.parse(raw));
  } catch {
    return [];
  }
}

export function writeGuestFavorites(ids: number[]) {
  window.localStorage.setItem(storageKey, JSON.stringify(normalizeIds(ids)));
  notifyFavoritesUpdated();
}

export function isGuestFavorite(productId: number) {
  return readGuestFavorites().includes(productId);
}

export async function loadFavoriteIds(): Promise<number[]> {
  if (!getToken()) {
    return readGuestFavorites();
  }

  const guestIds = readGuestFavorites();
  if (guestIds.length > 0) {
    await Promise.allSettled(guestIds.map((id) => addFavorite(id)));
    window.localStorage.removeItem(storageKey);
  }

  const favorites = await getFavorites();

  return favorites.map((product) => product.id);
}

export async function toggleFavorite(productId: number, current: boolean): Promise<boolean> {
  const next = !current;

  if (getToken()) {
    if (next) {
      await addFavorite(productId);
    } else {
      await removeFavorite(productId);
    }
  } else {
    const favorites = readGuestFavorites();
    writeGuestFavorites(next
      ? [...favorites, productId]
      : favorites.filter((id) => id !== productId));
  }

  notifyFavoritesUpdated();

  return next;
}
