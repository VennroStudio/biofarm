import { api } from '@/lib/api';

export interface User {
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
}

export interface ReferralInfo {
  referredUsers: number;
  totalEarnings: number;
  pendingEarnings: number;
  referralPercent: number;
}

const USER_STORAGE_KEY = 'currentUser';

export const authApi = {
  login: async (email: string, password: string): Promise<User | null> => {
    try {
      const data = await api.auth.login(email, password);
      const user: User = {
        id: String(data.id),
        email: data.email,
        name: data.name,
        phone: data.phone || undefined,
        bonusBalance: data.bonusBalance || 0,
        isPartner: data.isPartner || false,
        isActive: data.isActive ?? true,
        cardNumber: data.cardNumber || undefined,
        createdAt: data.createdAt || new Date().toISOString(),
      };
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
      return user;
    } catch (error: any) {
      // Пробрасываем ошибку с сообщением от сервера
      throw new Error(error.message || 'Ошибка входа');
    }
  },

  register: async (email: string, password: string, name: string, referredBy?: string): Promise<User | null> => {
    try {
      const data = await api.auth.register(email, password, name, referredBy);
      const user: User = {
        id: String(data.id),
        email: data.email,
        name: data.name,
        phone: data.phone || undefined,
        bonusBalance: data.bonusBalance || 0,
        isPartner: data.isPartner || false,
        isActive: data.isActive ?? true,
        cardNumber: data.cardNumber || undefined,
        createdAt: data.createdAt || new Date().toISOString(),
        referredBy: referredBy || undefined,
      };
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
      return user;
    } catch (error: any) {
      // Пробрасываем ошибку с сообщением от сервера
      throw new Error(error.message || 'Ошибка регистрации');
    }
  },

  logout: async (): Promise<void> => {
    localStorage.removeItem(USER_STORAGE_KEY);
  },

  getCurrentUser: (): User | null => {
    const stored = localStorage.getItem(USER_STORAGE_KEY);
    return stored ? JSON.parse(stored) : null;
  },

  refreshUser: async (userId: string): Promise<User | null> => {
    try {
      const data = await api.auth.getCurrent(Number(userId));
      const user: User = {
        id: String(data.id),
        email: data.email,
        name: data.name,
        phone: data.phone || undefined,
        bonusBalance: data.bonusBalance || 0,
        isPartner: data.isPartner || false,
        isActive: data.isActive ?? true,
        cardNumber: data.cardNumber || undefined,
        createdAt: data.createdAt || new Date().toISOString(),
      };
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
      return user;
    } catch (error) {
      console.error('Failed to refresh user:', error);
      return null;
    }
  },

  updateProfile: async (userId: string, data: Partial<User>): Promise<User | null> => {
    try {
      const userData = await api.auth.updateProfile(Number(userId), {
        name: data.name,
        phone: data.phone,
        cardNumber: data.cardNumber,
      });
      const user: User = {
        id: String(userData.id),
        email: userData.email,
        name: userData.name,
        phone: userData.phone,
        cardNumber: userData.cardNumber,
        bonusBalance: data.bonusBalance ?? 0,
        isPartner: data.isPartner ?? false,
        createdAt: data.createdAt ?? new Date().toISOString(),
      };
      const current = authApi.getCurrentUser();
      if (current?.id === userId) {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
      }
      return user;
    } catch {
      return null;
    }
  },

  getReferralInfo: async (userId: string): Promise<ReferralInfo> => {
    const data = await api.auth.getReferralInfo(Number(userId));
    return {
      referredUsers: data.referredUsers || 0,
      totalEarnings: data.totalEarnings || 0,
      pendingEarnings: data.pendingEarnings || 0,
      referralPercent: data.referralPercent || 5,
    };
  },

  getAllUsers: async (): Promise<User[]> => {
    try {
      const data = await api.auth.getAllUsers();
      return data.map((u: any) => ({
        id: String(u.id),
        email: u.email,
        name: u.name,
        phone: u.phone,
        bonusBalance: u.bonusBalance ?? 0,
        isPartner: u.isPartner ?? false,
        isActive: u.isActive ?? true,
        cardNumber: u.cardNumber,
        createdAt: new Date().toISOString(),
      }));
    } catch (error) {
      console.error('Failed to load users:', error);
      return [];
    }
  },

      setPartnerStatus: async (userId: string, isPartner: boolean): Promise<User | null> => {
        try {
          const currentUser = authApi.getCurrentUser();
          if (!currentUser) throw new Error('User not logged in');
          const updatedUser = await api.auth.updateUser(parseInt(userId), { isPartner });
          if (updatedUser) {
            const userData: User = {
              id: String(updatedUser.id),
              email: updatedUser.email,
              name: updatedUser.name,
              phone: updatedUser.phone,
              bonusBalance: updatedUser.bonusBalance ?? 0,
              isPartner: updatedUser.isPartner ?? false,
              isActive: updatedUser.isActive ?? true,
              cardNumber: updatedUser.cardNumber ?? undefined,
              createdAt: currentUser.createdAt,
              updatedAt: new Date().toISOString(),
            };
            return userData;
          }
          return null;
        } catch (error) {
          console.error('Failed to set partner status:', error);
          return null;
        }
      },

  updateCardNumber: async (userId: string, cardNumber: string): Promise<User | null> => {
    return authApi.updateProfile(userId, { cardNumber });
  },

  deductBalance: async (userId: string, amount: number): Promise<User | null> => {
    // TODO: Implement deduct balance API endpoint
    return null;
  },
};

export const users: User[] = [];
