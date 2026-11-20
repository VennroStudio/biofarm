import type { Category } from '../../../types';

export type CategoryForm = {
  id?: number;
  name: string;
  slug: string;
  parent_id: string;
  h1: string;
  seo_title: string;
  seo_description: string;
  intro_text: string;
  bottom_text: string;
  image: string;
  is_indexable: boolean;
  sort_order: string;
};

export const emptyCategoryForm: CategoryForm = {
  name: '',
  slug: '',
  parent_id: '',
  h1: '',
  seo_title: '',
  seo_description: '',
  intro_text: '',
  bottom_text: '',
  image: '',
  is_indexable: true,
  sort_order: '0',
};

export function categoryFormFromCategory(category: Category): CategoryForm {
  return {
    id: category.id,
    name: category.name,
    slug: category.slug,
    parent_id: category.parent_id ? String(category.parent_id) : '',
    h1: category.h1 ?? '',
    seo_title: category.seo_title ?? '',
    seo_description: category.seo_description ?? '',
    intro_text: category.intro_text ?? '',
    bottom_text: category.bottom_text ?? '',
    image: category.image ?? '',
    is_indexable: category.is_indexable,
    sort_order: String(category.sort_order),
  };
}

export function categoryPayloadFromForm(form: CategoryForm) {
  return {
    name: form.name,
    slug: form.slug || null,
    parentId: form.parent_id ? Number(form.parent_id) : null,
    h1: form.h1 || null,
    seoTitle: form.seo_title || null,
    seoDescription: form.seo_description || null,
    introText: form.intro_text || null,
    bottomText: form.bottom_text || null,
    image: form.image || null,
    isIndexable: form.is_indexable,
    sortOrder: form.sort_order ? Number(form.sort_order) : 0,
  };
}
