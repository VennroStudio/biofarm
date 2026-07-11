import type {
  AdminCustomer,
  ApiItems,
  BlogPost,
  Category,
  DashboardStats,
  Order,
  Product,
  Review,
  Settings,
  Withdrawal,
} from '../types';
import { request, requestItems } from './client';

export const dashboardApi = {
  get: () => request<DashboardStats>('/admin/api/dashboard'),
};

export const settingsApi = {
  get: () => request<Settings>('/admin/api/settings'),
  update: (payload: Partial<Settings>) => request<Partial<Settings>>('/admin/api/settings', {
    method: 'PATCH',
    body: payload as Record<string, unknown>,
  }),
};

export const productsApi = {
  list: (search = '') => requestItems<Product>(`/v1/products?includeInactive=true&perPage=100${search ? `&search=${encodeURIComponent(search)}` : ''}`),
  create: (payload: Record<string, unknown>) => request('/v1/products/create', { method: 'POST', body: payload }),
  update: (id: number, payload: Record<string, unknown>) => request(`/v1/products/update/${id}`, { method: 'PATCH', body: payload }),
  delete: (id: number) => request(`/v1/products/delete/${id}`, { method: 'DELETE' }),
};

export const categoriesApi = {
  list: () => requestItems<Category>('/v1/product-categories?perPage=100'),
  create: (payload: Record<string, unknown>) => request('/v1/product-categories/create', { method: 'POST', body: payload }),
  update: (id: number, payload: Record<string, unknown>) => request(`/v1/product-categories/update/${id}`, { method: 'PATCH', body: payload }),
  delete: (id: number) => request(`/v1/product-categories/delete/${id}`, { method: 'DELETE' }),
};

export const blogApi = {
  list: () => requestItems<BlogPost>('/v1/blog?onlyPublished=false&perPage=100'),
  create: (payload: Record<string, unknown>) => request('/v1/blog/create', { method: 'POST', body: payload }),
  update: (id: number, payload: Record<string, unknown>) => request(`/v1/blog/update/${id}`, { method: 'PATCH', body: payload }),
  delete: (id: number) => request(`/v1/blog/delete/${id}`, { method: 'DELETE' }),
};

export const reviewsApi = {
  list: () => requestItems<Review>('/v1/reviews?onlyApproved=false'),
  create: (payload: Record<string, unknown>) => request('/v1/reviews/create', { method: 'POST', body: payload }),
  update: (id: string, payload: Record<string, unknown>) => request(`/v1/reviews/update/${id}`, { method: 'PATCH', body: payload }),
  approve: (id: string) => request(`/admin/api/reviews/${id}/approve`, { method: 'PATCH', body: {} }),
  delete: (id: string) => request(`/v1/reviews/delete/${id}`, { method: 'DELETE' }),
};

export const ordersApi = {
  list: () => requestItems<Order>('/v1/orders?perPage=100'),
  updateStatus: (id: string, status: string) => request(`/admin/api/orders/${id}/status`, { method: 'PATCH', body: { status } }),
  updatePaymentStatus: (id: string, payment_status: string) =>
    request(`/admin/api/orders/${id}/payment-status`, { method: 'PATCH', body: { payment_status } }),
};

export const usersApi = {
  list: () => requestItems<AdminCustomer>('/admin/api/users?perPage=100'),
  update: (id: number, payload: Record<string, unknown>) => request(`/admin/api/users/${id}`, { method: 'PATCH', body: payload }),
};

export const withdrawalsApi = {
  list: () => requestItems<Withdrawal>('/admin/api/withdrawals'),
  create: (payload: { user_id: number; amount: number }) => request('/admin/api/withdrawals', { method: 'POST', body: payload }),
  setStatus: (id: string, status: 'approved' | 'rejected') =>
    request(`/admin/api/withdrawals/${id}/status`, { method: 'PATCH', body: { status } }),
};

export type ResourceResult<T> = ApiItems<T>;
