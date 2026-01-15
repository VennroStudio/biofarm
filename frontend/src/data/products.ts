import { api } from '@/lib/api';

export interface Product {
  id: number;
  slug: string;
  name: string;
  category: string;
  price: number;
  oldPrice?: number;
  image: string;
  images?: string[];
  badge?: string;
  weight: string;
  description: string;
  shortDescription?: string;
  ingredients?: string;
  features?: string[];
  wbLink?: string;
  ozonLink?: string;
  isActive?: boolean;
}

export interface Category {
  id: string;
  label: string;
}

// Categories are static
export const categories: Category[] = [
  { id: 'all', label: 'Все товары' },
  { id: 'honey', label: 'Мёд' },
  { id: 'oils', label: 'Масла' },
];

let cachedProducts: Product[] | null = null;

export const getProducts = async (includeInactive: boolean = false): Promise<Product[]> => {
  if (cachedProducts && !includeInactive) return cachedProducts;
  const data = await api.products.getAll(includeInactive);
  const mapped = data.map((p: any) => ({
    id: p.id,
    slug: p.slug,
    name: p.name,
    category: p.category,
    price: p.price,
    oldPrice: p.oldPrice ?? undefined,
    image: p.image,
    images: p.images,
    badge: p.badge ?? undefined,
    weight: p.weight,
    description: p.description,
    shortDescription: p.shortDescription ?? undefined,
    ingredients: p.ingredients ?? undefined,
    features: p.features,
    wbLink: p.wbLink ?? undefined,
    ozonLink: p.ozonLink ?? undefined,
    isActive: p.isActive ?? true,
  }));
  if (!includeInactive) {
    cachedProducts = mapped;
  }
  return mapped;
};

export const getProductBySlug = async (slug: string): Promise<Product | undefined> => {
  try {
    const data = await api.products.getBySlug(slug);
    return {
      id: data.id,
      slug: data.slug,
      name: data.name,
      category: data.category,
      price: data.price,
      oldPrice: data.oldPrice ?? undefined,
      image: data.image,
      images: data.images,
      badge: data.badge ?? undefined,
      weight: data.weight,
      description: data.description,
      shortDescription: data.shortDescription ?? undefined,
      ingredients: data.ingredients ?? undefined,
      isActive: data.isActive ?? true,
      features: data.features,
      wbLink: data.wbLink ?? undefined,
      ozonLink: data.ozonLink ?? undefined,
    };
  } catch {
    return undefined;
  }
};

export const getProductsByCategory = async (categoryId: string): Promise<Product[]> => {
  const allProducts = await getProducts();
  if (categoryId === 'all') return allProducts;
  return allProducts.filter((p) => p.category === categoryId);
};

// For backward compatibility
export const products: Product[] = [];
