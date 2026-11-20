import type { ProductGroupDetails, ProductGroupMember } from '../../../types';

export type ProductGroupForm = {
  id?: number;
  name: string;
  products: ProductGroupMember[];
  mainProductId: number | null;
  revision?: string;
};

export const emptyProductGroupForm: ProductGroupForm = {
  name: '',
  products: [],
  mainProductId: null,
};

export function productGroupFormFromGroup(group: ProductGroupDetails): ProductGroupForm {
  return {
    id: group.id,
    name: group.name,
    products: group.products,
    mainProductId: group.main_product_id,
    revision: group.revision,
  };
}

export function productGroupPayloadFromForm(form: ProductGroupForm) {
  return {
    name: form.name.trim(),
    product_ids: form.products.map(product => product.id),
    main_product_id: form.mainProductId,
    revision: form.revision,
  };
}
