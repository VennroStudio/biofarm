import type { AdminUser, ApiItems, MediaAsset } from '../types';

const tokenKey = 'biofarm_admin_access_token';
const adminKey = 'biofarm_admin_user';

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | null;
};

type ApiEnvelope<T> = {
  data: T;
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
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = getToken();
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

  const response = await fetch(path, {
    ...options,
    headers,
    body: normalizedBody,
  });

  if (response.status === 401 || response.status === 403) {
    clearSession();
  }

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? ((await response.json()) as ApiEnvelope<T> | { error?: string })
    : null;

  if (!response.ok) {
    const message = payload && 'error' in payload && payload.error ? payload.error : `HTTP ${response.status}`;
    throw new Error(message);
  }

  if (payload && 'data' in payload) {
    return payload.data;
  }

  return undefined as T;
}

export async function requestItems<T>(path: string): Promise<ApiItems<T>> {
  return request<ApiItems<T>>(path);
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
  await request<void>('/admin/api/auth/logout', { method: 'POST' }).catch(() => undefined);
  clearSession();
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
