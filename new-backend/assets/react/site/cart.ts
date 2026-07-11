export type CartProduct = {
  id: number;
  slug: string;
  name: string;
  title?: string;
  image: string;
  price: number;
  weight?: string;
};

export type CartItem = {
  product: CartProduct;
  quantity: number;
};

const storageKey = 'biofarm_cart';
const legacyStorageKey = 'cart';

function normalizeProduct(product: CartProduct): CartProduct {
  return {
    ...product,
    name: product.name || product.title || 'Товар',
  };
}

function normalizeItem(value: unknown): CartItem | null {
  if (typeof value !== 'object' || value === null || !('product' in value)) {
    return null;
  }

  const record = value as { product?: unknown; quantity?: unknown };
  const product = record.product;
  if (typeof product !== 'object' || product === null) {
    return null;
  }

  const productRecord = product as Partial<CartProduct>;
  const id = Number(productRecord.id);
  const price = Number(productRecord.price);
  const rawQuantity = Number(record.quantity || 1);
  const quantity = Number.isFinite(rawQuantity) ? Math.max(1, rawQuantity) : 1;

  if (!Number.isFinite(id) || !Number.isFinite(price)) {
    return null;
  }

  return {
    product: normalizeProduct({
      id,
      slug: String(productRecord.slug || ''),
      name: String(productRecord.name || productRecord.title || 'Товар'),
      title: productRecord.title,
      image: String(productRecord.image || ''),
      price,
      weight: productRecord.weight,
    }),
    quantity,
  };
}

function notifyCartUpdated() {
  window.dispatchEvent(new Event('biofarm-cart-updated'));
  window.dispatchEvent(new Event('cartUpdated'));
}

export function readCart(): CartItem[] {
  const raw = window.localStorage.getItem(storageKey) || window.localStorage.getItem(legacyStorageKey);
  if (!raw) {
    return [];
  }

  try {
    const data = JSON.parse(raw) as unknown;
    if (!Array.isArray(data)) {
      return [];
    }

    return data.map(normalizeItem).filter((item): item is CartItem => item !== null);
  } catch {
    return [];
  }
}

export function writeCart(cart: CartItem[]) {
  window.localStorage.setItem(storageKey, JSON.stringify(cart));
  window.localStorage.removeItem(legacyStorageKey);
  notifyCartUpdated();
}

export function addToCart(product: CartProduct, quantity = 1) {
  const cart = readCart();
  const normalized = normalizeProduct(product);
  const existing = cart.find((item) => item.product.id === normalized.id);

  if (existing) {
    existing.quantity += quantity;
  } else {
    cart.push({ product: normalized, quantity });
  }

  writeCart(cart);

  return cart;
}

export function updateQuantity(productId: number, quantity: number) {
  let cart = readCart();

  if (quantity <= 0) {
    cart = cart.filter((item) => item.product.id !== productId);
  } else {
    const item = cart.find((cartItem) => cartItem.product.id === productId);
    if (item) {
      item.quantity = quantity;
    }
  }

  writeCart(cart);

  return cart;
}

export function removeFromCart(productId: number) {
  const cart = readCart().filter((item) => item.product.id !== productId);
  writeCart(cart);

  return cart;
}

export function clearCart() {
  window.localStorage.removeItem(storageKey);
  window.localStorage.removeItem(legacyStorageKey);
  notifyCartUpdated();
}

export function cartTotal(cart = readCart()) {
  return cart.reduce((sum, item) => sum + item.product.price * item.quantity, 0);
}

export function cartCount(cart = readCart()) {
  return cart.reduce((sum, item) => sum + item.quantity, 0);
}
