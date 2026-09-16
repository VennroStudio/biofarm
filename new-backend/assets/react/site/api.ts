import type { CartItem } from './cart';

export function getReferralCode(): string | undefined {
 try { const saved=JSON.parse(window.localStorage.getItem('biofarm_referral')||'null');if(saved&&typeof saved.code==='string'&&Number(saved.expiresAt)>Date.now())return saved.code; } catch { /* Invalid storage is discarded. */ }
 window.localStorage.removeItem('biofarm_referral');window.localStorage.removeItem('referralCode');return undefined;
}

const tokenKey = 'biofarm_access_token';
const userKey = 'biofarm_user';
let refreshPromise: Promise<string | null> | null = null;

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: FormData | Record<string, unknown> | null;
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

export type SiteUser = {
  id: string;
  email: string;
  name: string;
  phone?: string;
  avatar?: string;
  createdAt: string;
  referredBy?: string;
  bonusBalance: number;
  isPartner: boolean;
  cardNumber?: string;
  referralCode?: string;
};

export type ShippingAddress = {
  name: string;
  phone: string;
  email: string;
  city: string;
  address: string;
  postalCode: string;
  comment?: string;
};

export type UserAddress = ShippingAddress & {
  id: number;
  label: string;
  isDefault: boolean;
  createdAt: string;
  updatedAt?: null | string;
};

export type OrderItem = {
  productId: number;
  productName: string;
  price: number;
  quantity: number;
};

export type SiteOrder = {
  id: string;
  userId: null | string;
  items: OrderItem[];
  status: 'cancelled' | 'delivered' | 'pending' | 'processing' | 'shipped';
  paymentStatus: 'completed' | 'failed' | 'pending' | 'refunded';
  subtotal: number;
  deliveryMethod?: string;
  deliveryCost: number;
  discountAmount: number;
  promoCode?: string;
  total: number;
  bonusUsed: number;
  bonusEarned: number;
  createdAt: string;
  paidAt: null | string;
  shippingAddress: ShippingAddress;
  paymentMethod: string;
  trackingNumber?: string;
};

export type ReferralInfo = {
  referredUsers: number;
  totalEarnings: number;
  pendingEarnings: number;
  referralPercent: number;
  referralCode: string;
};

export type WithdrawalRequest = {
  id: string;
  amount: number;
  status: 'approved' | 'pending' | 'rejected';
  createdAt: string;
  processedAt?: null | string;
};

export type FavoriteProduct = {
  id: number;
  slug: string;
  name: string;
  title: string;
  image: string;
  imageAlt?: string;
  price: number;
  oldPrice?: number | null;
  weight: string;
  shortDescription?: string;
  favoritedAt?: string;
};

export function getToken() {
  return window.localStorage.getItem(tokenKey);
}

export function getStoredUser(): SiteUser | null {
  const raw = window.localStorage.getItem(userKey);
  if (!raw) {
    return null;
  }

  try {
    return mapUser(JSON.parse(raw));
  } catch {
    return null;
  }
}

export function clearAuth() {
  window.localStorage.removeItem(tokenKey);
  window.localStorage.removeItem(userKey);
}

async function refreshAccessToken(failedToken: string): Promise<string | null> {
  const refresh = async () => {
    // A different request/tab may already have refreshed or cleared the session.
    if (getToken() !== failedToken) {
      return getToken();
    }

    const response = await fetch('/v1/auth/refresh', {
      method: 'POST',
      credentials: 'same-origin',
    });

    if (getToken() !== failedToken) {
      return getToken();
    }

    // The endpoint returns 422 when the refresh cookie is missing.
    if (response.status === 401 || response.status === 422) {
      clearAuth();
      return null;
    }

    if (!response.ok) {
      throw new Error('Не удалось продлить вход. Попробуйте ещё раз.');
    }

    const payload: unknown = await response.json();
    if (!isRecord(payload) || !isRecord(payload.data) || typeof payload.data.access_token !== 'string' || !payload.data.access_token) {
      throw new Error('Не удалось продлить вход. Попробуйте ещё раз.');
    }

    // Do not restore credentials if the user logged out while reading the response.
    if (getToken() !== failedToken) {
      return getToken();
    }

    window.localStorage.setItem(tokenKey, payload.data.access_token);
    return payload.data.access_token;
  };

  if (!refreshPromise) {
    // Refresh cookies rotate, so only one tab may use the current cookie at a time.
    refreshPromise = Promise.resolve(navigator.locks
      ? navigator.locks.request('biofarm-site-token-refresh', refresh)
      : refresh()).finally(() => { refreshPromise = null; });
  }

  return refreshPromise;
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  let token = getToken();
  const isAuthRequest = path.startsWith('/v1/auth/');
  const headers = new Headers(options.headers);

  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  let body: BodyInit | undefined;
  if (options.body instanceof FormData) {
    body = options.body;
  } else if (options.body !== undefined && options.body !== null) {
    headers.set('Content-Type', 'application/json');
    body = JSON.stringify(options.body);
  }

  const send = () => fetch(path, { ...options, body, headers, credentials: 'same-origin' });
  let response = await send();

  if (response.status === 401 && token && !isAuthRequest) {
    token = await refreshAccessToken(token);
    if (token) {
      headers.set('Authorization', `Bearer ${token}`);
      response = await send();
    }
  }

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? ((await response.json()) as ApiEnvelope<T> | ApiErrorEnvelope)
    : null;

  if (response.status === 401 && !isAuthRequest && token && getToken() === token) {
    clearAuth();
  }

  if (!response.ok) {
    throw new Error(errorMessage(payload, `HTTP ${response.status}`));
  }

  if (payload && 'data' in payload) {
    return payload.data as T;
  }

  return undefined as T;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

function stringValue(value: unknown, fallback = '') {
  return typeof value === 'string' ? value : fallback;
}

function numberValue(value: unknown, fallback = 0) {
  const number = Number(value);

  return Number.isFinite(number) ? number : fallback;
}

function boolValue(value: unknown) {
  return value === true || value === 1 || value === '1';
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

function mapUser(value: unknown): SiteUser | null {
  if (!isRecord(value)) {
    return null;
  }

  const firstName = stringValue(value.first_name);
  const lastName = stringValue(value.last_name);
  const name = stringValue(value.name, `${firstName} ${lastName}`.trim());

  return {
    id: String(value.id || ''),
    email: stringValue(value.email),
    name,
    phone: stringValue(value.phone) || undefined,
    avatar: stringValue(value.avatar) || undefined,
    createdAt: stringValue(value.created_at, stringValue(value.createdAt, new Date().toISOString())),
    referredBy: value.referred_by_user_id !== null && value.referred_by_user_id !== undefined
      ? String(value.referred_by_user_id)
      : undefined,
    bonusBalance: numberValue(value.bonus_balance ?? value.bonusBalance),
    isPartner: boolValue(value.is_partner ?? value.isPartner),
    cardNumber: stringValue(value.card_number ?? value.cardNumber) || undefined,
    referralCode: stringValue(value.referral_code ?? value.referralCode) || undefined,
  };
}

function mapShippingAddress(value: unknown): ShippingAddress {
  if (!isRecord(value)) {
    return { name: '', phone: '', email: '', city: '', address: '', postalCode: '' };
  }

  return {
    name: stringValue(value.name),
    phone: stringValue(value.phone),
    email: stringValue(value.email),
    city: stringValue(value.city),
    address: stringValue(value.address),
    postalCode: stringValue(value.postal_code ?? value.postalCode),
    comment: stringValue(value.comment) || undefined,
  };
}

function mapUserAddress(value: unknown): UserAddress | null {
  if (!isRecord(value)) {
    return null;
  }

  const id = numberValue(value.id);
  if (id <= 0) {
    return null;
  }

  return {
    ...mapShippingAddress(value),
    id,
    label: stringValue(value.label, 'Адрес'),
    isDefault: boolValue(value.is_default ?? value.isDefault),
    createdAt: stringValue(value.created_at ?? value.createdAt),
    updatedAt: stringValue(value.updated_at ?? value.updatedAt) || null,
  };
}

function mapOrderItem(value: unknown): OrderItem | null {
  if (!isRecord(value)) {
    return null;
  }

  return {
    productId: numberValue(value.product_id ?? value.productId),
    productName: stringValue(value.product_name ?? value.productName, 'Товар'),
    price: numberValue(value.price),
    quantity: numberValue(value.quantity, 1),
  };
}

function mapOrder(value: unknown): SiteOrder | null {
  if (!isRecord(value)) {
    return null;
  }

  const items = Array.isArray(value.items)
    ? value.items.map(mapOrderItem).filter((item): item is OrderItem => item !== null)
    : [];

  return {
    id: String(value.id || ''),
    userId: value.user_id !== null && value.user_id !== undefined
      ? String(value.user_id)
      : value.userId !== null && value.userId !== undefined
        ? String(value.userId)
        : null,
    items,
    status: stringValue(value.status, 'pending') as SiteOrder['status'],
    paymentStatus: stringValue(value.payment_status ?? value.paymentStatus, 'pending') as SiteOrder['paymentStatus'],
    subtotal: numberValue(value.subtotal, numberValue(value.total)),
    deliveryMethod: stringValue(value.delivery_method ?? value.deliveryMethod) || undefined,
    deliveryCost: numberValue(value.delivery_cost ?? value.deliveryCost),
    discountAmount: numberValue(value.discount_amount ?? value.discountAmount),
    promoCode: stringValue(value.promo_code ?? value.promoCode) || undefined,
    total: numberValue(value.total),
    bonusUsed: numberValue(value.bonus_used ?? value.bonusUsed),
    bonusEarned: numberValue(value.bonus_earned ?? value.bonusEarned),
    createdAt: stringValue(value.created_at ?? value.createdAt, new Date().toISOString()),
    paidAt: stringValue(value.paid_at ?? value.paidAt) || null,
    shippingAddress: mapShippingAddress(value.shipping_address ?? value.shippingAddress),
    paymentMethod: stringValue(value.payment_method ?? value.paymentMethod, 'card'),
    trackingNumber: stringValue(value.tracking_number ?? value.trackingNumber) || undefined,
  };
}

function mapFavoriteProduct(value: unknown): FavoriteProduct | null {
  if (!isRecord(value)) {
    return null;
  }

  const id = numberValue(value.id);
  if (id <= 0) {
    return null;
  }

  const name = stringValue(value.name ?? value.title, 'Товар');

  return {
    id,
    slug: stringValue(value.slug),
    name,
    title: stringValue(value.title, name),
    image: stringValue(value.image),
    imageAlt: stringValue(value.image_alt ?? value.imageAlt) || undefined,
    price: numberValue(value.price),
    oldPrice: value.old_price !== null && value.old_price !== undefined
      ? numberValue(value.old_price)
      : value.oldPrice !== null && value.oldPrice !== undefined
        ? numberValue(value.oldPrice)
        : null,
    weight: stringValue(value.weight),
    shortDescription: stringValue(value.short_description ?? value.shortDescription) || undefined,
    favoritedAt: stringValue(value.favorited_at ?? value.favoritedAt) || undefined,
  };
}

function mapItemsResponse<T>(value: unknown, mapper: (item: unknown) => T | null): T[] {
  const items = isRecord(value) && Array.isArray(value.items) ? value.items : [];

  return items.map(mapper).filter((item): item is T => item !== null);
}

function splitName(name: string) {
  const parts = name.trim().split(/\s+/u).filter(Boolean);

  return {
    firstName: parts[0] || 'Пользователь',
    lastName: parts.slice(1).join(' ') || 'БИОФАРМ',
  };
}

export async function login(email: string, password: string) {
  const data = await request<{ access_token: string }>('/v1/auth/login', {
    method: 'POST',
    body: { email, password },
  });

  window.localStorage.setItem(tokenKey, data.access_token);

  return refreshUser();
}

export async function register(email: string, password: string, name: string, referredBy?: string) {
  const { firstName, lastName } = splitName(name);
  await request('/v1/users/create', {
    method: 'POST',
    body: {
      email,
      firstName,
      lastName,
      password,
      referredBy,
    },
  });
}

export async function refreshUser() {
  const user = mapUser(await request('/v1/users/me'));
  if (user) {
    window.localStorage.setItem(userKey, JSON.stringify(user));
  }

  return user;
}

export async function updateProfile(payload: { cardNumber?: string; name?: string; phone?: string }) {
  const user = mapUser(await request('/v1/users/me', {
    method: 'PATCH',
    body: {
      cardNumber: payload.cardNumber,
      name: payload.name,
      phone: payload.phone,
    },
  }));

  if (user) {
    window.localStorage.setItem(userKey, JSON.stringify(user));
  }

  return user;
}

export async function getUserAddresses() {
  const data = await request('/v1/users/me/addresses');

  return mapItemsResponse(data, mapUserAddress);
}

export async function saveUserAddress(payload: Partial<UserAddress> & ShippingAddress, id?: number) {
  const data = await request(id ? `/v1/users/me/addresses/${id}` : '/v1/users/me/addresses', {
    method: id ? 'PATCH' : 'POST',
    body: {
      label: payload.label,
      name: payload.name,
      phone: payload.phone,
      email: payload.email,
      city: payload.city,
      address: payload.address,
      postalCode: payload.postalCode,
      comment: payload.comment,
      isDefault: payload.isDefault,
    },
  });

  return mapUserAddress(data);
}

export async function deleteUserAddress(id: number) {
  await request(`/v1/users/me/addresses/${id}`, {
    method: 'DELETE',
  });
}

export async function getOrders() {
  const data = await request('/v1/orders?perPage=100');

  return mapItemsResponse(data, mapOrder);
}

export async function getReferralOrders() {
  const data = await request('/v1/users/me/referral-orders');

  return mapItemsResponse(data, mapOrder);
}

export async function getReferralInfo(): Promise<ReferralInfo> {
  const data = await request('/v1/users/me/referral-info');
  const record = isRecord(data) ? data : {};

  return {
    referredUsers: numberValue(record.referred_users ?? record.referredUsers),
    totalEarnings: numberValue(record.total_earnings ?? record.totalEarnings),
    pendingEarnings: numberValue(record.pending_earnings ?? record.pendingEarnings),
    referralPercent: numberValue(record.referral_percent ?? record.referralPercent, 5),
    referralCode: stringValue(record.referral_code ?? record.referralCode),
  };
}

export async function createOrder(
  cart: CartItem[],
  shippingAddress: ShippingAddress,
  paymentMethod: string,
  deliveryMethod: string,
  useBonuses: boolean,
  promoCode?: string,
) {
  const referralCode = getReferralCode();
  const storedUser = getStoredUser();
  const body = {
      userId: storedUser?.id ? Number(storedUser.id) : null,
      items: cart.map((item) => ({
        productId: item.product.id,
        quantity: item.quantity,
      })),
      shippingAddress,
      paymentMethod,
      deliveryMethod,
      useBonuses,
      promoCode: promoCode?.trim() || undefined,
      referredBy: referralCode,
      offerId: window.sessionStorage.getItem('biofarm_offer_id') || undefined,
  };
  const fingerprint = JSON.stringify(body);
  const previous = window.sessionStorage.getItem('biofarm_checkout_request');
  const saved = previous ? JSON.parse(previous) as { key: string; fingerprint: string } : null;
  const key = saved?.fingerprint === fingerprint ? saved.key : crypto.randomUUID();
  window.sessionStorage.setItem('biofarm_checkout_request', JSON.stringify({key, fingerprint}));
  const data = await request('/v1/orders/create', {
    method: 'POST', headers: {'Idempotency-Key': key}, body,
  });

  if (isRecord(data) && data.id && data.paymentAccessToken) window.sessionStorage.setItem(`biofarm_payment_${data.id}`, String(data.paymentAccessToken));
  return mapOrder({
    ...(isRecord(data) ? data : {}),
    items: cart.map((item) => ({
      product_id: item.product.id,
      product_name: item.product.name,
      price: item.product.price,
      quantity: item.quantity,
    })),
    status: 'pending',
    payment_status: 'pending',
    shipping_address: shippingAddress,
    payment_method: paymentMethod,
    delivery_method: deliveryMethod,
  });
}

export async function getWithdrawals() {
  const data = await request('/v1/withdrawals');

  return mapItemsResponse(data, (item): WithdrawalRequest | null => {
    if (!isRecord(item)) {
      return null;
    }

    return {
      id: String(item.id || ''),
      amount: numberValue(item.amount),
      status: stringValue(item.status, 'pending') as WithdrawalRequest['status'],
      createdAt: stringValue(item.created_at ?? item.createdAt),
      processedAt: stringValue(item.processed_at ?? item.processedAt) || null,
    };
  });
}

export async function createWithdrawal(amount: number) {
  await request('/v1/withdrawals/create', {
    method: 'POST',
    body: { amount },
  });
}

export async function getFavorites() {
  const data = await request('/v1/favorites');

  return mapItemsResponse(data, mapFavoriteProduct);
}

export async function addFavorite(productId: number) {
  await request(`/v1/favorites/${productId}`, {
    method: 'POST',
  });
}

export async function removeFavorite(productId: number) {
  await request(`/v1/favorites/${productId}`, {
    method: 'DELETE',
  });
}
