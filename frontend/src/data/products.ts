import productsData from './products.json';

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
  shortDescription: string;
  ingredients?: string;
  features?: string[];
  wbLink?: string;
  ozonLink?: string;
}

export interface Category {
  id: string;
  label: string;
}

// Transform JSON data to match the expected interface
export const categories: Category[] = productsData.categories;

export const products: Product[] = productsData.products.map(p => ({
  id: p.id,
  slug: p.slug,
  name: p.name,
  category: p.category_id,
  price: p.price,
  oldPrice: p.old_price ?? undefined,
  image: p.image,
  images: p.images,
  badge: p.badge ?? undefined,
  weight: p.weight,
  description: p.description,
  shortDescription: p.short_description,
  ingredients: p.ingredients ?? undefined,
  features: p.features,
  wbLink: p.wb_link ?? undefined,
  ozonLink: p.ozon_link ?? undefined,
}));

export const getProductBySlug = (slug: string): Product | undefined => {
  return products.find((p) => p.slug === slug);
};

export const getProductsByCategory = (categoryId: string): Product[] => {
  if (categoryId === 'all') return products;
  return products.filter((p) => p.category === categoryId);
};
