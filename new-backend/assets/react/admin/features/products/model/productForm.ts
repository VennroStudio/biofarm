import { listFromLines } from '../../../shared/lib';
import type { Product, ProductImage } from '../../../types';

export type ProductImageForm = {
  path: string;
  alt: string;
  title: string;
  sort_order: number;
  is_main: boolean;
};

export type ProductForm = {
  id?: number;
  name: string;
  slug: string;
  h1: string;
  seo_title: string;
  seo_description: string;
  category_id: string;
  price: string;
  old_price: string;
  weight: string;
  sku: string;
  gtin: string;
  availability: string;
  short_description: string;
  description: string;
  ingredients: string;
  attribute_value_ids: number[];
  product_group_id: string;
  image: string;
  image_alt: string;
  image_items: ProductImageForm[];
  badge: string;
  features: string;
  wb_link: string;
  ozon_link: string;
  is_active: boolean;
};

export const emptyProductForm: ProductForm = {
  name: '',
  slug: '',
  h1: '',
  seo_title: '',
  seo_description: '',
  category_id: '',
  price: '',
  old_price: '',
  weight: '',
  sku: '',
  gtin: '',
  availability: 'in_stock',
  short_description: '',
  description: '',
  ingredients: '',
  attribute_value_ids: [],
  product_group_id: '',
  image: '',
  image_alt: '',
  image_items: [],
  badge: '',
  features: '',
  wb_link: '',
  ozon_link: '',
  is_active: true,
};

export function productFormFromProduct(product: Product): ProductForm {
  const imageItems = productImageItems(product);
  const mainImage = imageItems.find((image) => image.is_main) ?? imageItems[0];

  return {
    id: product.id,
    name: product.name,
    slug: product.slug,
    h1: product.h1 ?? '',
    seo_title: product.seo_title ?? '',
    seo_description: product.seo_description ?? '',
    category_id: product.category_id,
    price: String(product.price),
    old_price: product.old_price ? String(product.old_price) : '',
    weight: product.weight,
    sku: product.sku ?? '',
    gtin: product.gtin ?? '',
    availability: product.availability,
    short_description: product.short_description ?? '',
    description: product.description,
    ingredients: product.ingredients ?? '',
    attribute_value_ids: product.attribute_value_ids ?? [],
    product_group_id: product.product_group_id ? String(product.product_group_id) : '',
    image: mainImage?.path ?? product.image,
    image_alt: mainImage?.alt ?? product.image_alt ?? '',
    image_items: imageItems,
    badge: product.badge ?? '',
    features: (product.features ?? []).join('\n'),
    wb_link: product.wb_link ?? '',
    ozon_link: product.ozon_link ?? '',
    is_active: product.is_active,
  };
}

export function productPayloadFromForm(form: ProductForm) {
  const imageItems = normalizedImageItems(form);
  const mainImage = imageItems.find((image) => image.is_main) ?? imageItems[0];
  const imagePaths = imageItems.map((image) => image.path);

  return {
    name: form.name,
    slug: form.slug || null,
    h1: form.h1 || null,
    seoTitle: form.seo_title || null,
    seoDescription: form.seo_description || null,
    categoryId: form.category_id,
    price: Number(form.price),
    oldPrice: form.old_price ? Number(form.old_price) : null,
    image: mainImage?.path || '',
    imageAlt: mainImage?.alt || form.image_alt || null,
    images: imagePaths,
    productImages: imageItems.map((image, index) => ({
      path: image.path,
      alt: image.alt || null,
      title: image.title || null,
      sortOrder: index,
      isMain: image.is_main,
    })),
    badge: form.badge || null,
    weight: form.weight,
    sku: form.sku || null,
    gtin: form.gtin || null,
    availability: form.availability || 'in_stock',
    shortDescription: form.short_description || null,
    description: form.description,
    ingredients: form.ingredients || null,
    attributeValueIds: form.attribute_value_ids,
    productGroupId: form.product_group_id ? Number(form.product_group_id) : null,
    features: listFromLines(form.features),
    wbLink: form.wb_link || null,
    ozonLink: form.ozon_link || null,
    isActive: form.is_active,
  };
}

export function productPayloadFromProduct(product: Product, overrides: Partial<ProductForm> = {}) {
  return productPayloadFromForm({ ...productFormFromProduct(product), ...overrides });
}

export function imageItem(path: string, index: number, isMain = false): ProductImageForm {
  return {
    path,
    alt: '',
    title: '',
    sort_order: index,
    is_main: isMain,
  };
}

export function hasProductImage(form: ProductForm) {
  return normalizedImageItems(form).length > 0;
}

export function setMainImage(items: ProductImageForm[], index: number) {
  return items.map((item, itemIndex) => ({
    ...item,
    is_main: itemIndex === index,
    sort_order: itemIndex,
  }));
}

function productImageItems(product: Product): ProductImageForm[] {
  if (product.product_images && product.product_images.length > 0) {
    const sorted = [...product.product_images].sort((left, right) => left.sort_order - right.sort_order);

    return ensureMain(sorted.map((image: ProductImage, index) => ({
      path: image.path,
      alt: image.alt ?? '',
      title: image.title ?? '',
      sort_order: index,
      is_main: image.is_main,
    })));
  }

  const paths = product.images && product.images.length > 0
    ? product.images
    : (product.image ? [product.image] : []);

  return ensureMain(paths.map((path, index) => ({
    path,
    alt: index === 0 ? (product.image_alt ?? '') : '',
    title: product.name,
    sort_order: index,
    is_main: index === 0,
  })));
}

function normalizedImageItems(form: ProductForm): ProductImageForm[] {
  const byPath = new Map<string, ProductImageForm>();
  const source = form.image_items.length > 0
    ? form.image_items
    : (form.image ? [imageItem(form.image, 0, true)] : []);

  source.forEach((item, index) => {
    const path = item.path.trim();
    if (!path || byPath.has(path)) {
      return;
    }

    byPath.set(path, {
      path,
      alt: item.alt.trim(),
      title: item.title.trim(),
      sort_order: index,
      is_main: item.is_main,
    });
  });

  return ensureMain([...byPath.values()]);
}

function ensureMain(items: ProductImageForm[]): ProductImageForm[] {
  if (items.length === 0) {
    return [];
  }

  const mainIndex = items.findIndex((item) => item.is_main);

  return items.map((item, index) => ({
    ...item,
    sort_order: index,
    is_main: index === (mainIndex >= 0 ? mainIndex : 0),
  }));
}
