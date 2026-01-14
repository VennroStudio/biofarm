// User & Auth data layer - easily replaceable with API calls
import usersData from './users.json';

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

// Load users from JSON and transform to our interface
const loadUsers = (): User[] => {
  const stored = localStorage.getItem('biofarm_users');
  if (stored) {
    return JSON.parse(stored);
  }
  return usersData.users.map(u => ({
    id: u.id,
    email: u.email,
    name: u.name,
    phone: u.phone,
    createdAt: u.created_at,
    referredBy: u.referred_by || undefined,
    bonusBalance: u.bonus_balance,
    isPartner: u.is_partner,
    cardNumber: u.card_number || undefined,
  }));
};

const saveUsers = (users: User[]) => {
  localStorage.setItem('biofarm_users', JSON.stringify(users));
};

export const users: User[] = loadUsers();

const USER_STORAGE_KEY = 'currentUser';

// API abstraction layer - replace these with actual API calls later
export const authApi = {
  login: async (email: string, password: string): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 500));
    const allUsers = loadUsers();
    const user = allUsers.find(u => u.email === email);
    if (user) {
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
      return user;
    }
    return null;
  },

  register: async (email: string, password: string, name: string, referredBy?: string): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 500));
    const allUsers = loadUsers();
    
    const newUser: User = {
      id: String(Date.now()),
      email,
      name,
      createdAt: new Date().toISOString(),
      referredBy,
      bonusBalance: 0,
      isPartner: false,
    };
    
    allUsers.push(newUser);
    saveUsers(allUsers);
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(newUser));
    return newUser;
  },

  logout: async (): Promise<void> => {
    localStorage.removeItem(USER_STORAGE_KEY);
  },

  getCurrentUser: (): User | null => {
    const stored = localStorage.getItem(USER_STORAGE_KEY);
    return stored ? JSON.parse(stored) : null;
  },

  updateProfile: async (userId: string, data: Partial<User>): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allUsers = loadUsers();
    const index = allUsers.findIndex(u => u.id === userId);
    if (index >= 0) {
      allUsers[index] = { ...allUsers[index], ...data };
      saveUsers(allUsers);
      const current = authApi.getCurrentUser();
      if (current?.id === userId) {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(allUsers[index]));
      }
      return allUsers[index];
    }
    return null;
  },

  getReferralInfo: async (userId: string): Promise<ReferralInfo> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const stats = usersData.referral_stats[userId as keyof typeof usersData.referral_stats];
    const { adminApi } = await import('./admin');
    const settings = await adminApi.getReferralSettings();
    
    return {
      referredUsers: stats?.referred_users || 0,
      totalEarnings: stats?.total_earnings || 0,
      pendingEarnings: stats?.pending_earnings || 0,
      referralPercent: settings.referralPercent,
    };
  },

  getAllUsers: async (): Promise<User[]> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return loadUsers();
  },

  setPartnerStatus: async (userId: string, isPartner: boolean): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allUsers = loadUsers();
    const index = allUsers.findIndex(u => u.id === userId);
    if (index >= 0) {
      allUsers[index].isPartner = isPartner;
      saveUsers(allUsers);
      return allUsers[index];
    }
    return null;
  },

  updateCardNumber: async (userId: string, cardNumber: string): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allUsers = loadUsers();
    const index = allUsers.findIndex(u => u.id === userId);
    if (index >= 0) {
      allUsers[index].cardNumber = cardNumber;
      saveUsers(allUsers);
      return allUsers[index];
    }
    return null;
  },

  deductBalance: async (userId: string, amount: number): Promise<User | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const allUsers = loadUsers();
    const index = allUsers.findIndex(u => u.id === userId);
    if (index >= 0 && allUsers[index].bonusBalance >= amount) {
      allUsers[index].bonusBalance -= amount;
      saveUsers(allUsers);
      return allUsers[index];
    }
    return null;
  },
};
