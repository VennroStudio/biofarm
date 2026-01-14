// Withdrawal requests data layer
import withdrawalsData from './withdrawals.json';

export interface WithdrawalRequest {
  id: string;
  userId: string;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  processedAt?: string;
  processedBy?: string;
}

const STORAGE_KEY = 'biofarm_withdrawals';

const loadWithdrawals = (): WithdrawalRequest[] => {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored) {
    return JSON.parse(stored);
  }
  return withdrawalsData.withdrawals.map(w => ({
    id: w.id,
    userId: w.user_id,
    amount: w.amount,
    status: w.status as WithdrawalRequest['status'],
    createdAt: w.created_at,
    processedAt: w.processed_at || undefined,
    processedBy: w.processed_by || undefined,
  }));
};

const saveWithdrawals = (data: WithdrawalRequest[]) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
};

export const withdrawalsApi = {
  getAll: async (): Promise<WithdrawalRequest[]> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return loadWithdrawals();
  },

  getByUser: async (userId: string): Promise<WithdrawalRequest[]> => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return loadWithdrawals().filter(w => w.userId === userId);
  },

  create: async (userId: string, amount: number): Promise<WithdrawalRequest> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const requests = loadWithdrawals();
    const newRequest: WithdrawalRequest = {
      id: `wd-${Date.now()}`,
      userId,
      amount,
      status: 'pending',
      createdAt: new Date().toISOString(),
    };
    requests.push(newRequest);
    saveWithdrawals(requests);
    return newRequest;
  },

  approve: async (id: string, adminName: string): Promise<WithdrawalRequest | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const requests = loadWithdrawals();
    const index = requests.findIndex(w => w.id === id);
    if (index >= 0) {
      const withdrawal = requests[index];
      
      // Deduct balance from user
      const { authApi } = await import('./users');
      await authApi.deductBalance(withdrawal.userId, withdrawal.amount);
      
      requests[index] = {
        ...requests[index],
        status: 'approved',
        processedAt: new Date().toISOString(),
        processedBy: adminName,
      };
      saveWithdrawals(requests);
      return requests[index];
    }
    return null;
  },

  reject: async (id: string, adminName: string): Promise<WithdrawalRequest | null> => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const requests = loadWithdrawals();
    const index = requests.findIndex(w => w.id === id);
    if (index >= 0) {
      requests[index] = {
        ...requests[index],
        status: 'rejected',
        processedAt: new Date().toISOString(),
        processedBy: adminName,
      };
      saveWithdrawals(requests);
      return requests[index];
    }
    return null;
  },
};
