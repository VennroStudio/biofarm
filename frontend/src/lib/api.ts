const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

async function request<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Unknown error' }));
    throw new Error(error.error || `HTTP error! status: ${response.status}`);
  }

  return response.json();
}

export const api = {
  // Products
  products: {
    getAll: (includeInactive?: boolean, category?: string) => {
      const params = new URLSearchParams();
      if (category) params.append('category', category);
      if (includeInactive) params.append('includeInactive', 'true');
      const query = params.toString();
      return request<any[]>(`/api/v1/products${query ? `?${query}` : ''}`);
    },
    getBySlug: (slug: string) =>
      request<any>(`/api/v1/products/${slug}`),
    create: (productData: any) =>
      request<any>('/api/v1/products', {
        method: 'POST',
        body: JSON.stringify(productData),
      }),
    update: (id: number, productData: any) =>
      request<any>(`/api/v1/products/${id}`, {
        method: 'PUT',
        body: JSON.stringify(productData),
      }),
    delete: (id: number) =>
      request<any>(`/api/v1/products/${id}`, {
        method: 'DELETE',
      }),
  },

  // Categories
  categories: {
    getAll: (activeOnly?: boolean) =>
      request<any[]>(activeOnly ? '/api/v1/categories?activeOnly=true' : '/api/v1/categories'),
    create: (categoryData: any) =>
      request<any>('/api/v1/categories', {
        method: 'POST',
        body: JSON.stringify(categoryData),
      }),
    update: (id: number, categoryData: any) =>
      request<any>(`/api/v1/categories/${id}`, {
        method: 'PUT',
        body: JSON.stringify(categoryData),
      }),
    delete: (id: number) =>
      request<any>(`/api/v1/categories/${id}`, {
        method: 'DELETE',
      }),
  },

  // Auth/Users
  auth: {
    login: (email: string, password: string) =>
      request<any>('/api/v1/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      }),
    register: (email: string, password: string, name: string, referredBy?: string) =>
      request<any>('/api/v1/auth/register', {
        method: 'POST',
        body: JSON.stringify({ email, password, name, referredBy }),
      }),
    getCurrent: (userId: number) =>
      request<any>(`/api/v1/auth/me?userId=${userId}`),
    getReferralInfo: (userId: number) =>
      request<any>(`/api/v1/auth/referral-info?userId=${userId}`),
    updateProfile: (userId: number, data: { name?: string; phone?: string; cardNumber?: string; isPartner?: boolean; isActive?: boolean }) =>
      request<any>('/api/v1/auth/profile', {
        method: 'PUT',
        body: JSON.stringify({ userId, ...data }),
      }),
    updateUser: (userId: number, data: { name?: string; phone?: string; cardNumber?: string; isPartner?: boolean; isActive?: boolean }) =>
      request<any>('/api/v1/auth/profile', {
        method: 'PUT',
        body: JSON.stringify({ userId, ...data }),
      }),
    getAllUsers: () =>
      request<any[]>('/api/v1/users'),
  },

  // Orders
  orders: {
    getAll: () =>
      request<any[]>('/api/v1/orders'),
    getByUserId: (userId: number) =>
      request<any[]>(`/api/v1/orders/user/${userId}`),
    getByReferrerId: (referrerId: number) =>
      request<any[]>(`/api/v1/orders/referrer/${referrerId}`),
    create: (orderData: any) =>
      request<any>('/api/v1/orders', {
        method: 'POST',
        body: JSON.stringify(orderData),
      }),
    updateStatus: (orderId: string, status: string) =>
      request<any>(`/api/v1/orders/${orderId}/status`, {
        method: 'PUT',
        body: JSON.stringify({ status }),
      }),
    updatePaymentStatus: (orderId: string, paymentStatus: string) =>
      request<any>(`/api/v1/orders/${orderId}/payment-status`, {
        method: 'PUT',
        body: JSON.stringify({ paymentStatus }),
      }),
  },

  // Reviews
  reviews: {
    getAll: (onlyApproved: boolean = true) =>
      request<any[]>(`/api/v1/reviews${onlyApproved ? '' : '?all=1'}`),
    getByProductId: (productId: number) =>
      request<any[]>(`/api/v1/reviews/product?productId=${productId}`),
    create: (reviewData: any) =>
      request<any>('/api/v1/reviews', {
        method: 'POST',
        body: JSON.stringify(reviewData),
      }),
    update: (reviewId: string, reviewData: any) =>
      request<any>(`/api/v1/reviews/${reviewId}`, {
        method: 'PUT',
        body: JSON.stringify(reviewData),
      }),
    approve: (reviewId: string) =>
      request<any>(`/api/v1/reviews/${reviewId}/approve`, {
        method: 'PUT',
      }),
    delete: (reviewId: string) =>
      request<any>(`/api/v1/reviews/${reviewId}`, {
        method: 'DELETE',
      }),
  },

  // Blog
  blog: {
    getAll: () =>
      request<any[]>('/api/v1/blog'),
    getBySlug: (slug: string) =>
      request<any>(`/api/v1/blog/${slug}`),
  },

  // Withdrawals
  withdrawals: {
    getAll: () =>
      request<any[]>('/api/v1/withdrawals'),
    getByUserId: (userId: number) =>
      request<any[]>(`/api/v1/withdrawals/user?userId=${userId}`),
    create: (withdrawalData: any) =>
      request<any>('/api/v1/withdrawals', {
        method: 'POST',
        body: JSON.stringify(withdrawalData),
      }),
    updateStatus: (withdrawalId: string, status: string, processedBy?: string) =>
      request<any>(`/api/v1/withdrawals/${withdrawalId}/status`, {
        method: 'PUT',
        body: JSON.stringify({ status, processedBy }),
      }),
  },

  // Admin
  admin: {
    login: (email: string, password: string) =>
      request<any>('/api/v1/admin/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      }),
    getCurrent: (adminId: number) =>
      request<any>(`/api/v1/admin/me?adminId=${adminId}`),
    changePassword: (adminId: number, currentPassword: string, newPassword: string) =>
      request<any>('/api/v1/admin/password', {
        method: 'PUT',
        body: JSON.stringify({ adminId, currentPassword, newPassword }),
      }),
  },
};
