import type { AdminUser, ApiItems, MediaAsset } from '../types';

const tokenKey = 'biofarm_admin_access_token';
const adminKey = 'biofarm_admin_user';
let refreshPromise: Promise<string | null> | null = null;
export const sessionClearedEvent = 'biofarm-admin-session-cleared';

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | null;
};

type ApiEnvelope<T> = {
  data: T;
};

type ApiErrorEnvelope = {
  error?: string | {
    description?: string;
    message?: string;
  };
  validations?: Array<{
    field: string;
    message: string;
  }>;
};

export function getToken() {
  return localStorage.getItem(tokenKey);
}

export function getStoredAdmin(): AdminUser | null {
  const raw = localStorage.getItem(adminKey);
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw) as AdminUser;
  } catch {
    return null;
  }
}

export function clearSession() {
  localStorage.removeItem(tokenKey);
  localStorage.removeItem(adminKey);
  window.dispatchEvent(new Event(sessionClearedEvent));
}

async function refreshAccessToken(failedToken: string): Promise<string | null> {
  const refresh = async () => {
    if (getToken() !== failedToken) return getToken();
    const response = await fetch('/admin/api/auth/refresh', { method: 'POST', credentials: 'same-origin' });
    if (getToken() !== failedToken) return getToken();
    if ([401, 403, 422].includes(response.status)) {
      clearSession();
      return null;
    }
    if (!response.ok) throw new Error('Не удалось продлить вход в админку. Попробуйте ещё раз.');
    const payload = await response.json() as Partial<ApiEnvelope<{ access_token?: string }>>;
    if (!payload.data?.access_token || typeof payload.data.access_token !== 'string') {
      throw new Error('Не удалось продлить вход в админку. Попробуйте ещё раз.');
    }
    if (getToken() !== failedToken) return getToken();
    localStorage.setItem(tokenKey, payload.data.access_token);
    return payload.data.access_token;
  };
  if (!refreshPromise) {
    refreshPromise = Promise.resolve(navigator.locks
      ? navigator.locks.request('biofarm-admin-token-refresh', refresh)
      : refresh()).finally(() => { refreshPromise = null; });
  }
  return refreshPromise;
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  let token = getToken();
  const isAuthRequest = ['/admin/api/auth/login', '/admin/api/auth/logout', '/admin/api/auth/refresh'].includes(path);
  const headers = new Headers(options.headers);
  const body = options.body;

  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  let normalizedBody: BodyInit | undefined;
  if (body instanceof FormData) {
    normalizedBody = body;
  } else if (body !== undefined && body !== null) {
    headers.set('Content-Type', 'application/json');
    normalizedBody = JSON.stringify(body);
  }

  const send = () => fetch(path, {
    ...options,
    headers,
    body: normalizedBody,
    credentials: 'same-origin',
  });
  let response = await send();

  if (response.status === 401 && token && !isAuthRequest) {
    token = await refreshAccessToken(token);
    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
      response = await send();
    }
  }
  if (response.status === 401 && !isAuthRequest && token && getToken() === token) {
    clearSession();
  }

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? ((await response.json()) as ApiEnvelope<T> | ApiErrorEnvelope)
    : null;

  if (!response.ok) {
    throw new Error(errorMessage(payload, errorFallback(response.status)));
  }

  if (payload && 'data' in payload) {
    return payload.data;
  }

  return undefined as T;
}

export async function requestItems<T>(path: string): Promise<ApiItems<T>> {
  return request<ApiItems<T>>(path);
}

function errorMessage(payload: ApiEnvelope<unknown> | ApiErrorEnvelope | null, fallback: string) {
  if (payload && 'validations' in payload && Array.isArray(payload.validations) && payload.validations.length > 0) {
    return payload.validations.map((item) => item.message).join('\n');
  }

  if (!payload || !('error' in payload) || !payload.error) {
    return fallback;
  }

  if (typeof payload.error === 'string') {
    return payload.error;
  }

  return payload.error.message || payload.error.description || fallback;
}

function errorFallback(status: number) {
  if (status === 413) {
    return 'Файл слишком большой для загрузки. Проверьте лимит nginx/client_max_body_size и размер изображения.';
  }

  return `HTTP ${status}`;
}

export async function login(email: string, password: string): Promise<AdminUser> {
  const data = await request<{ access_token: string; admin: AdminUser }>('/admin/api/auth/login', {
    method: 'POST',
    body: { email, password },
  });

  localStorage.setItem(tokenKey, data.access_token);
  localStorage.setItem(adminKey, JSON.stringify(data.admin));

  return data.admin;
}

export async function me(): Promise<AdminUser> {
  const admin = await request<AdminUser>('/admin/api/auth/me');
  localStorage.setItem(adminKey, JSON.stringify(admin));

  return admin;
}

export async function logout() {
  clearSession();
  await request<void>('/admin/api/auth/logout', { method: 'POST' }).catch(() => undefined);
}

export async function uploadImage(file: File, scope: string): Promise<MediaAsset> {
  const form = new FormData();
  form.append('file', file);
  form.append('scope', scope);

  return request<MediaAsset>('/admin/api/media', {
    method: 'POST',
    body: form,
  });
}
