import { api } from '@/lib/api';
import { Product } from './products';

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface OrderItem {
  productId: number;
  productName: string;
  price: number;
  quantity: number;
}

export interface Order {
  id: string;
  userId: string;
  items: OrderItem[];
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  paymentStatus: 'pending' | 'completed' | 'failed' | 'refunded';
  total: number;
  bonusUsed: number;
  bonusEarned: number;
  createdAt: string;
  paidAt: string | null;
  shippingAddress: ShippingAddress;
  paymentMethod: string;
  trackingNumber?: string;
}

export interface ShippingAddress {
  name: string;
  phone: string;
  email: string;
  city: string;
  address: string;
  postalCode: string;
  comment?: string;
}


// Cart API abstraction
export const cartApi = {
  getCart: (): CartItem[] => {
    const stored = localStorage.getItem('cart');
    return stored ? JSON.parse(stored) : [];
  },

  addToCart: (product: Product, quantity: number = 1): CartItem[] => {
    const cart = cartApi.getCart();
    const existing = cart.find(item => item.product.id === product.id);
    
    if (existing) {
      existing.quantity += quantity;
    } else {
      cart.push({ product, quantity });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    window.dispatchEvent(new Event('cartUpdated'));
    return cart;
  },

  updateQuantity: (productId: number, quantity: number): CartItem[] => {
    let cart = cartApi.getCart();
    
    if (quantity <= 0) {
      cart = cart.filter(item => item.product.id !== productId);
    } else {
      const item = cart.find(item => item.product.id === productId);
      if (item) {
        item.quantity = quantity;
      }
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    window.dispatchEvent(new Event('cartUpdated'));
    return cart;
  },

  removeFromCart: (productId: number): CartItem[] => {
    const cart = cartApi.getCart().filter(item => item.product.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    window.dispatchEvent(new Event('cartUpdated'));
    return cart;
  },

  clearCart: (): void => {
    localStorage.removeItem('cart');
    window.dispatchEvent(new Event('cartUpdated'));
  },

  getTotal: (): number => {
    return cartApi.getCart().reduce((sum, item) => sum + item.product.price * item.quantity, 0);
  },

  getItemCount: (): number => {
    return cartApi.getCart().reduce((sum, item) => sum + item.quantity, 0);
  },
};

// Orders API abstraction
export const ordersApi = {
  getOrders: async (userId: string): Promise<Order[]> => {
    const data = await api.orders.getByUserId(Number(userId));
    return data.map((o: any) => ({
      id: String(o.id),
      userId: String(o.userId),
      items: o.items || [],
      status: o.status as Order['status'],
      paymentStatus: o.paymentStatus as Order['paymentStatus'],
      total: o.total,
      bonusUsed: o.bonusUsed || 0,
      bonusEarned: o.bonusEarned || 0,
      createdAt: o.createdAt,
      paidAt: o.paidAt || null,
      shippingAddress: o.shippingAddress || { name: '', phone: '', email: '', city: '', address: '', postalCode: '' },
      paymentMethod: o.paymentMethod || 'card',
      trackingNumber: o.trackingNumber || undefined,
    }));
  },

  getAllOrders: async (): Promise<Order[]> => {
    const data = await api.orders.getAll();
    return data.map((o: any) => ({
      id: String(o.id),
      userId: String(o.userId),
      items: o.items || [],
      status: o.status as Order['status'],
      paymentStatus: o.paymentStatus as Order['paymentStatus'],
      total: o.total,
      bonusUsed: o.bonusUsed || 0,
      bonusEarned: o.bonusEarned || 0,
      createdAt: o.createdAt,
      paidAt: o.paidAt || null,
      shippingAddress: o.shippingAddress || { name: '', phone: '', email: '', city: '', address: '', postalCode: '' },
      paymentMethod: o.paymentMethod || 'card',
      trackingNumber: o.trackingNumber || undefined,
    }));
  },

  getReferralOrders: async (referrerId: string): Promise<Order[]> => {
    const data = await api.orders.getByReferrerId(Number(referrerId));
    return data.map((o: any) => ({
      id: String(o.id),
      userId: String(o.userId),
      items: o.items || [],
      status: o.status as Order['status'],
      paymentStatus: o.paymentStatus as Order['paymentStatus'],
      total: o.total,
      bonusUsed: o.bonusUsed || 0,
      bonusEarned: o.bonusEarned || 0,
      createdAt: o.createdAt,
      paidAt: o.paidAt || null,
      shippingAddress: o.shippingAddress || { name: '', phone: '', email: '', city: '', address: '', postalCode: '' },
      paymentMethod: o.paymentMethod || 'card',
      trackingNumber: o.trackingNumber || undefined,
    }));
  },

  createOrder: async (
    userId: string,
    items: CartItem[],
    shippingAddress: ShippingAddress,
    paymentMethod: string,
    bonusUsed: number = 0
  ): Promise<Order> => {
    const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0) - bonusUsed;
    
    // Получаем реферальный код из localStorage
    const referralCode = localStorage.getItem('referralCode') || undefined;
    
    const data = await api.orders.create({
      userId: Number(userId) || 0,
      items: items.map(i => ({
        productId: i.product.id,
        productName: i.product.name,
        price: i.product.price,
        quantity: i.quantity,
      })),
      total,
      shippingAddress,
      paymentMethod,
      bonusUsed,
      referredBy: referralCode,
    });
    
    cartApi.clearCart();
    
    return {
      id: data.id,
      userId: String(data.userId),
      items: items.map(i => ({
        productId: i.product.id,
        productName: i.product.name,
        price: i.product.price,
        quantity: i.quantity,
      })),
      status: 'pending',
      paymentStatus: 'pending',
      total: data.total,
      bonusUsed,
      bonusEarned: 0,
      createdAt: new Date().toISOString(),
      paidAt: null,
      shippingAddress,
      paymentMethod,
    };
  },

  updateOrderStatus: async (orderId: string, status: Order['status']): Promise<Order | null> => {
    const data = await api.orders.updateStatus(orderId, status);
    if (!data) return null;
    
    return {
      id: data.id,
      userId: String(data.userId),
      items: [],
      status: data.status as Order['status'],
      paymentStatus: 'pending',
      total: data.total,
      bonusUsed: 0,
      bonusEarned: 0,
      createdAt: new Date().toISOString(),
      paidAt: null,
      shippingAddress: { name: '', phone: '', email: '', city: '', address: '', postalCode: '' },
      paymentMethod: 'card',
    };
  },

  updatePaymentStatus: async (orderId: string, paymentStatus: Order['paymentStatus']): Promise<Order | null> => {
    const data = await api.orders.updatePaymentStatus(orderId, paymentStatus);
    if (!data) return null;
    
    // Получаем полную информацию о заказе
    const allOrders = await ordersApi.getAllOrders();
    const order = allOrders.find(o => o.id === orderId);
    
    if (!order) return null;
    
    return {
      ...order,
      paymentStatus: data.paymentStatus as Order['paymentStatus'],
      bonusEarned: data.bonusEarned || 0,
    };
  },
};
