import type { CartItem } from './cart';

const tokenKey = 'biofarm_access_token';
const userKey = 'biofarm_user';

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: FormData | Record<string, unknown> | null;
};

type ApiEnvelope<T> = {
  data: T;
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

export type OrderItem = {
  productId: number;
  productName: string;
  price: number;
  quantity: number;
};

export type SiteOrder = {
  id: string;
  userId: string;
  items: OrderItem[];
  status: 'cancelled' | 'delivered' | 'pending' | 'processing' | 'shipped';
  paymentStatus: 'completed' | 'failed' | 'pending' | 'refunded';
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

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = getToken();
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

  const response = await fetch(path, { ...options, body, headers });
  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? ((await response.json()) as ApiEnvelope<T> | { data?: unknown; error?: string })
    : null;

  if (response.status === 401 || response.status === 403) {
    clearAuth();
  }

  if (!response.ok) {
    const message = payload && 'error' in payload && payload.error ? payload.error : `HTTP ${response.status}`;
    throw new Error(message);
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
    userId: String(value.user_id ?? value.userId ?? ''),
    items,
    status: stringValue(value.status, 'pending') as SiteOrder['status'],
    paymentStatus: stringValue(value.payment_status ?? value.paymentStatus, 'pending') as SiteOrder['paymentStatus'],
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

export async function createOrder(cart: CartItem[], shippingAddress: ShippingAddress, paymentMethod: string, bonusUsed: number, total: number) {
  const referralCode = window.localStorage.getItem('referralCode') || undefined;
  const data = await request('/v1/orders/create', {
    method: 'POST',
    body: {
      userId: Number(getStoredUser()?.id || 0),
      items: cart.map((item) => ({
        productId: item.product.id,
        productName: item.product.name,
        price: item.product.price,
        quantity: item.quantity,
      })),
      total,
      shippingAddress,
      paymentMethod,
      bonusUsed,
      referredBy: referralCode,
    },
  });

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
