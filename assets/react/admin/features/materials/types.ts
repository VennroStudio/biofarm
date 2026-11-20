export type MaterialKind = 'certificate' | 'faq';
export type Placement = { target_type: string; target_id: string; title: string; url: string | null };
export type MaterialCategory = { id: number; name: string; usage_count: number };
export type Material = { id: number; title: string; question?: string; answer?: string; file_path?: string; document_type?: string; description?: string | null; category_id: number | null; category_name: string | null; is_active: boolean; usage_count: number; updated_at: string; placements?: Placement[] };
export type Paginated<T> = { items: T[]; total: number; page: number; per_page: number };
export type MaterialQuery = { search?: string; category_id?: number | string; is_active?: number | string; usage?: string; target_type?: string; target_id?: string; page?: number; per_page?: number; ids?: string };
export type MaterialPayload = { title?: string; question?: string; answer?: string; file_path?: string; document_type?: string; description?: string | null; category_id: number | null; is_active: boolean; placements?: Pick<Placement, 'target_type' | 'target_id'>[] };
