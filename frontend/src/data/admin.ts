// Admin API
import { api } from '@/lib/api';
import settingsData from './settings.json';

export interface AdminUser {
  id: number;
  email: string;
  name: string;
  role: 'admin' | 'moderator';
}

export interface ReferralSettings {
  referralPercent: number;
  orderBonusEnabled: boolean;
  orderBonusPercent: number;
}

export interface DashboardStats {
  totalOrders: number;
  totalRevenue: number;
  totalUsers: number;
  pendingWithdrawals: number;
  totalWithdrawalAmount: number;
}

// Settings storage
const SETTINGS_STORAGE_KEY = 'biofarm_settings';

const loadSettings = (): ReferralSettings => {
  const stored = localStorage.getItem(SETTINGS_STORAGE_KEY);
  return stored ? JSON.parse(stored) : settingsData;
};

const saveSettings = (settings: ReferralSettings) => {
  localStorage.setItem(SETTINGS_STORAGE_KEY, JSON.stringify(settings));
};

const ADMIN_STORAGE_KEY = 'biofarm_admin_session';

export const adminApi = {
  login: async (email: string, password: string): Promise<AdminUser | null> => {
    try {
      const admin = await api.admin.login(email, password);
      if (admin) {
        localStorage.setItem(ADMIN_STORAGE_KEY, JSON.stringify(admin));
        return admin;
      }
      return null;
    } catch (error: any) {
      throw new Error(error.message || 'Ошибка входа');
    }
  },

  logout: async (): Promise<void> => {
    localStorage.removeItem(ADMIN_STORAGE_KEY);
  },

  getCurrentAdmin: (): AdminUser | null => {
    const stored = localStorage.getItem(ADMIN_STORAGE_KEY);
    return stored ? JSON.parse(stored) : null;
  },

  refreshAdmin: async (): Promise<AdminUser | null> => {
    const currentAdmin = adminApi.getCurrentAdmin();
    if (!currentAdmin) return null;
    
    try {
      const admin = await api.admin.getCurrent(currentAdmin.id);
      if (admin) {
        localStorage.setItem(ADMIN_STORAGE_KEY, JSON.stringify(admin));
        return admin;
      }
      return null;
    } catch (error) {
      console.error('Failed to refresh admin:', error);
      return null;
    }
  },

  changePassword: async (currentPassword: string, newPassword: string): Promise<void> => {
    const currentAdmin = adminApi.getCurrentAdmin();
    if (!currentAdmin) {
      throw new Error('Admin not authenticated');
    }
    
    await api.admin.changePassword(currentAdmin.id, currentPassword, newPassword);
  },

  isAuthenticated: (): boolean => {
    return !!adminApi.getCurrentAdmin();
  },

  getReferralSettings: async (): Promise<ReferralSettings> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return loadSettings();
  },

  updateReferralSettings: async (settings: Partial<ReferralSettings>): Promise<ReferralSettings> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const current = loadSettings();
    const updated = { ...current, ...settings };
    saveSettings(updated);
    return updated;
  },

  // Dashboard stats calculated from actual data
  getDashboardStats: async (): Promise<DashboardStats> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    
    const { ordersApi } = await import('./orders');
    const { withdrawalsApi } = await import('./withdrawals');
    const { authApi } = await import('./users');
    
    const orders = await ordersApi.getAllOrders();
    const withdrawals = await withdrawalsApi.getAll();
    const users = await authApi.getAllUsers();
    
    const pendingWithdrawals = withdrawals.filter(w => w.status === 'pending');
    // Выручка считается по оплаченным заказам (paymentStatus === 'completed' или paidAt !== null)
    const paidOrders = orders.filter(o => o.paymentStatus === 'completed' || o.paidAt !== null);
    
    return {
      totalOrders: orders.length,
      totalRevenue: paidOrders.reduce((sum, o) => sum + o.total, 0),
      totalUsers: users.length,
      pendingWithdrawals: pendingWithdrawals.length,
      totalWithdrawalAmount: pendingWithdrawals.reduce((sum, w) => sum + w.amount, 0),
    };
  },
};
