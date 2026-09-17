import { request } from '../../api/client';
import type { Material, MaterialCategory, MaterialKind, MaterialPayload, MaterialQuery, Paginated, Placement } from './types';
function queryString(query: Record<string, string | number | undefined>) {
  const params = new URLSearchParams();
  Object.entries(query).forEach(([key, value]) => { if (value !== undefined && value !== '') params.set(key, String(value)); });
  return params.toString();
}
const base = (kind: MaterialKind) => `/admin/api/materials/${kind}`;
const categoryBase = (kind: MaterialKind) => `/admin/api/material-categories/${kind}`;
export const materialsApi = {
  list: (kind: MaterialKind, query: MaterialQuery = {}) => request<Paginated<Material>>(`${base(kind)}?${queryString(query)}`),
  get: (kind: MaterialKind, id: number) => request<Material>(`${base(kind)}/${id}`),
  create: (kind: MaterialKind, payload: MaterialPayload) => request<{ id: number }>(base(kind), { method: 'POST', body: payload }),
  update: (kind: MaterialKind, id: number, payload: MaterialPayload) => request<{ id: number }>(`${base(kind)}/${id}`, { method: 'PATCH', body: payload }),
  delete: (kind: MaterialKind, id: number) => request<void>(`${base(kind)}/${id}`, { method: 'DELETE' }),
  categories: (kind: MaterialKind) => request<{ items: MaterialCategory[] }>(categoryBase(kind)),
  createCategory: (kind: MaterialKind, name: string) => request<{ id: number }>(categoryBase(kind), { method: 'POST', body: { name } }),
  updateCategory: (kind: MaterialKind, id: number, name: string) => request<void>(`${categoryBase(kind)}/${id}`, { method: 'PATCH', body: { name } }),
  deleteCategory: (kind: MaterialKind, id: number) => request<void>(`${categoryBase(kind)}/${id}`, { method: 'DELETE' }),
  targets: (query: { search?: string; target_type?: string; page?: number; per_page?: number } = {}) => request<Paginated<Placement>>(`/admin/api/material-targets?${queryString(query)}`),
  selections: (type: string, id: string) => request<{ certificate_ids: number[]; faq_ids: number[] }>(`/admin/api/material-targets/${encodeURIComponent(type)}/${encodeURIComponent(id)}/selections`),
  bulkAttach: (kind: MaterialKind, ids: number[], placements: Pick<Placement, 'target_type' | 'target_id'>[]) => request<void>(`${base(kind)}/bulk-attach`, { method: 'POST', body: { ids, placements } }),
};
