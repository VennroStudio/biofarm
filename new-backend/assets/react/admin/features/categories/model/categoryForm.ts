import type { Category } from '../../../types';

export type CategoryForm = {
  id?: number;
  name: string;
  slug: string;
};

export const emptyCategoryForm: CategoryForm = {
  name: '',
  slug: '',
};

export function categoryFormFromCategory(category: Category): CategoryForm {
  return {
    id: category.id,
    name: category.name,
    slug: category.slug,
  };
}

export function categoryPayloadFromForm(form: CategoryForm) {
  return {
    name: form.name,
    slug: form.slug || null,
  };
}
