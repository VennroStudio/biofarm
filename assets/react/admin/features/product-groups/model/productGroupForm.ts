import type { ProductGroup } from '../../../types';

export type ProductGroupForm = {
  id?: number;
  name: string;
};

export const emptyProductGroupForm: ProductGroupForm = {
  name: '',
};

export function productGroupFormFromGroup(group: ProductGroup): ProductGroupForm {
  return {
    id: group.id,
    name: group.name,
  };
}

export function productGroupPayloadFromForm(form: ProductGroupForm) {
  return {
    name: form.name,
  };
}
