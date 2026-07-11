import { listFromLines } from '../../../shared/lib';
import type { Product } from '../../../types';

export type ProductForm = {
  id?: number;
  name: string;
  slug: string;
  category_id: string;
  price: string;
  old_price: string;
  weight: string;
  short_description: string;
  description: string;
  ingredients: string;
  image: string;
  images: string[];
  badge: string;
  features: string;
  wb_link: string;
  ozon_link: string;
  is_active: boolean;
};

export const emptyProductForm: ProductForm = {
  name: '',
  slug: '',
  category_id: '',
  price: '',
  old_price: '',
  weight: '',
  short_description: '',
  description: '',
  ingredients: '',
  image: '',
  images: [],
  badge: '',
  features: '',
  wb_link: '',
  ozon_link: '',
  is_active: true,
};

export function productFormFromProduct(product: Product): ProductForm {
  return {
    id: product.id,
    name: product.name,
    slug: product.slug,
    category_id: product.category_id,
    price: String(product.price),
    old_price: product.old_price ? String(product.old_price) : '',
    weight: product.weight,
    short_description: product.short_description ?? '',
    description: product.description,
    ingredients: product.ingredients ?? '',
    image: product.image,
    images: product.images ?? (product.image ? [product.image] : []),
    badge: product.badge ?? '',
    features: (product.features ?? []).join('\n'),
    wb_link: product.wb_link ?? '',
    ozon_link: product.ozon_link ?? '',
    is_active: product.is_active,
  };
}

export function productPayloadFromForm(form: ProductForm) {
  return {
    name: form.name,
    slug: form.slug || null,
    categoryId: form.category_id,
    price: Number(form.price),
    oldPrice: form.old_price ? Number(form.old_price) : null,
    image: form.image || form.images[0] || '',
    images: form.images.length > 0 ? form.images : (form.image ? [form.image] : []),
    badge: form.badge || null,
    weight: form.weight,
    shortDescription: form.short_description || null,
    description: form.description,
    ingredients: form.ingredients || null,
    features: listFromLines(form.features),
    wbLink: form.wb_link || null,
    ozonLink: form.ozon_link || null,
    isActive: form.is_active,
  };
}

export function productPayloadFromProduct(product: Product, overrides: Partial<ProductForm> = {}) {
  return productPayloadFromForm({ ...productFormFromProduct(product), ...overrides });
}
