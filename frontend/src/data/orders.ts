// Orders data layer - easily replaceable with API calls
import ordersData from './orders.json';
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

const ORDERS_STORAGE_KEY = 'biofarm_orders';

// Load orders from JSON and transform to our interface
const loadOrders = (): Order[] => {
  const stored = localStorage.getItem(ORDERS_STORAGE_KEY);
  if (stored) {
    return JSON.parse(stored);
  }
  return ordersData.orders.map(o => ({
    id: o.id,
    userId: o.user_id,
    items: o.items.map(i => ({
      productId: i.product_id,
      productName: i.product_name,
      price: i.price,
      quantity: i.quantity,
    })),
    status: o.status as Order['status'],
    paymentStatus: o.payment_status as Order['paymentStatus'],
    total: o.total,
    bonusUsed: o.bonus_used,
    bonusEarned: o.bonus_earned,
    createdAt: o.created_at,
    paidAt: o.paid_at,
    shippingAddress: {
      name: o.shipping_address.name,
      phone: o.shipping_address.phone,
      email: o.shipping_address.email,
      city: o.shipping_address.city,
      address: o.shipping_address.address,
      postalCode: o.shipping_address.postal_code,
    },
    paymentMethod: o.payment_method,
    trackingNumber: o.tracking_number || undefined,
  }));
};

const saveOrders = (orders: Order[]) => {
  localStorage.setItem(ORDERS_STORAGE_KEY, JSON.stringify(orders));
};

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
    await new Promise(resolve => setTimeout(resolve, 300));
    const allOrders = loadOrders();
    return allOrders.filter(o => o.userId === userId);
  },

  getAllOrders: async (): Promise<Order[]> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    return loadOrders();
  },

  createOrder: async (
    userId: string,
    items: CartItem[],
    shippingAddress: ShippingAddress,
    paymentMethod: string,
    bonusUsed: number = 0
  ): Promise<Order> => {
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0) - bonusUsed;
    const bonusEarned = Math.floor(total * 0.05);
    
    const order: Order = {
      id: `ORD-${Date.now().toString(36).toUpperCase()}`,
      userId,
      items: items.map(i => ({
        productId: i.product.id,
        productName: i.product.name,
        price: i.product.price,
        quantity: i.quantity,
      })),
      status: 'pending',
      paymentStatus: 'pending',
      total,
      bonusUsed,
      bonusEarned,
      createdAt: new Date().toISOString(),
      paidAt: null,
      shippingAddress,
      paymentMethod,
    };
    
    const allOrders = loadOrders();
    allOrders.unshift(order);
    saveOrders(allOrders);
    
    cartApi.clearCart();
    
    return order;
  },

  updateOrderStatus: async (orderId: string, status: Order['status']): Promise<Order | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allOrders = loadOrders();
    const order = allOrders.find(o => o.id === orderId);
    
    if (order) {
      order.status = status;
      saveOrders(allOrders);
      return order;
    }
    return null;
  },

  updatePaymentStatus: async (orderId: string, paymentStatus: Order['paymentStatus']): Promise<Order | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allOrders = loadOrders();
    const order = allOrders.find(o => o.id === orderId);
    
    if (order) {
      order.paymentStatus = paymentStatus;
      if (paymentStatus === 'completed') {
        order.paidAt = new Date().toISOString();
      }
      saveOrders(allOrders);
      return order;
    }
    return null;
  },
};
