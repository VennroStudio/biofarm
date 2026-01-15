import { api } from '@/lib/api';

export interface WithdrawalRequest {
  id: string;
  userId: string;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  createdAt: string;
  processedAt?: string;
  processedBy?: string;
}

export const withdrawalsApi = {
  getAll: async (): Promise<WithdrawalRequest[]> => {
    const data = await api.withdrawals.getAll();
    return data.map((w: any) => ({
      id: w.id,
      userId: String(w.userId),
      amount: w.amount,
      status: w.status as WithdrawalRequest['status'],
      createdAt: w.createdAt,
      processedAt: w.processedAt,
      processedBy: w.processedBy,
    }));
  },

  getByUser: async (userId: string): Promise<WithdrawalRequest[]> => {
    const data = await api.withdrawals.getByUserId(Number(userId));
    return data.map((w: any) => ({
      id: w.id,
      userId: String(w.userId),
      amount: w.amount,
      status: w.status as WithdrawalRequest['status'],
      createdAt: w.createdAt,
      processedAt: w.processedAt,
      processedBy: w.processedBy,
    }));
  },

  create: async (userId: string, amount: number): Promise<WithdrawalRequest> => {
    const data = await api.withdrawals.create({
      userId: Number(userId),
      amount,
    });
    
    return {
      id: data.id,
      userId: String(data.userId),
      amount: data.amount,
      status: data.status as WithdrawalRequest['status'],
      createdAt: new Date().toISOString(),
    };
  },

  approve: async (id: string, adminName: string): Promise<WithdrawalRequest | null> => {
    const data = await api.withdrawals.updateStatus(id, 'approved', adminName);
    if (!data) return null;
    
    return {
      id: data.id,
      userId: String(data.userId),
      amount: data.amount,
      status: 'approved',
      createdAt: new Date().toISOString(),
      processedAt: new Date().toISOString(),
      processedBy: adminName,
    };
  },

  reject: async (id: string, adminName: string): Promise<WithdrawalRequest | null> => {
    const data = await api.withdrawals.updateStatus(id, 'rejected', adminName);
    if (!data) return null;
    
    return {
      id: data.id,
      userId: String(data.userId),
      amount: data.amount,
      status: 'rejected',
      createdAt: new Date().toISOString(),
      processedAt: new Date().toISOString(),
      processedBy: adminName,
    };
  },
};
