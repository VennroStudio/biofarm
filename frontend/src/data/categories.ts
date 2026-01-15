import { api } from '@/lib/api';

export interface Category {
  id: number;
  slug: string;
  name: string;
  createdAt?: string;
  updatedAt?: string;
}

let cachedCategories: Category[] | null = null;

export const getCategories = async (activeOnly: boolean = false, forceRefresh: boolean = false): Promise<Category[]> => {
  if (cachedCategories && !activeOnly && !forceRefresh) return cachedCategories;
  const data = await api.categories.getAll(activeOnly);
  if (!activeOnly) {
    cachedCategories = data;
  }
  return data;
};

export const clearCategoriesCache = () => {
  cachedCategories = null;
};

export const categoriesApi = {
  getAll: (activeOnly?: boolean, forceRefresh?: boolean) => getCategories(activeOnly, forceRefresh),
  create: async (categoryData: { name: string; slug?: string }) => {
    clearCategoriesCache();
    return await api.categories.create(categoryData);
  },
  update: async (id: number, categoryData: { name: string; slug?: string }) => {
    clearCategoriesCache();
    return await api.categories.update(id, categoryData);
  },
  delete: async (id: number) => {
    clearCategoriesCache();
    return await api.categories.delete(id);
  },
};
