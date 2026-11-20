import { listFromLines } from '../../../shared/lib';
import type { AttributeValue, ProductAttribute } from '../../../types';

export type AttributeForm = {
  id?: number;
  name: string;
  slug: string;
  filter_prefix: string;
  is_filterable: boolean;
  is_indexable: boolean;
  show_on_product: boolean;
  sort_order: string;
};

export type AttributeValueForm = {
  id?: number;
  attribute_id: number;
  name: string;
  slug: string;
  h1: string;
  seo_title: string;
  seo_description: string;
  intro_text: string;
  bottom_text: string;
  short_description: string;
  synonyms: string;
  is_indexable: boolean;
  sort_order: string;
};

export const emptyAttributeForm: AttributeForm = {
  name: '',
  slug: '',
  filter_prefix: '',
  is_filterable: true,
  is_indexable: true,
  show_on_product: true,
  sort_order: '0',
};

export function attributeFormFromAttribute(attribute: ProductAttribute): AttributeForm {
  return {
    id: attribute.id,
    name: attribute.name,
    slug: attribute.slug,
    filter_prefix: attribute.filter_prefix ?? '',
    is_filterable: attribute.is_filterable,
    is_indexable: attribute.is_indexable,
    show_on_product: attribute.show_on_product,
    sort_order: String(attribute.sort_order),
  };
}

export function attributePayloadFromForm(form: AttributeForm) {
  return {
    name: form.name,
    slug: form.slug || null,
    filter_prefix: form.filter_prefix || null,
    is_filterable: form.is_filterable,
    is_indexable: form.is_indexable,
    show_on_product: form.show_on_product,
    sort_order: Number(form.sort_order || 0),
  };
}

export function emptyAttributeValueForm(attributeId: number): AttributeValueForm {
  return {
    attribute_id: attributeId,
    name: '',
    slug: '',
    h1: '',
    seo_title: '',
    seo_description: '',
    intro_text: '',
    bottom_text: '',
    short_description: '',
    synonyms: '',
    is_indexable: true,
    sort_order: '0',
  };
}

export function attributeValueFormFromValue(value: AttributeValue): AttributeValueForm {
  return {
    id: value.id,
    attribute_id: value.attribute_id,
    name: value.name,
    slug: value.slug,
    h1: value.h1 ?? '',
    seo_title: value.seo_title ?? '',
    seo_description: value.seo_description ?? '',
    intro_text: value.intro_text ?? '',
    bottom_text: value.bottom_text ?? '',
    short_description: value.short_description ?? '',
    synonyms: value.synonyms.join('\n'),
    is_indexable: value.is_indexable,
    sort_order: String(value.sort_order),
  };
}

export function attributeValuePayloadFromForm(form: AttributeValueForm) {
  return {
    name: form.name,
    slug: form.slug || null,
    h1: form.h1 || null,
    seo_title: form.seo_title || null,
    seo_description: form.seo_description || null,
    intro_text: form.intro_text || null,
    bottom_text: form.bottom_text || null,
    short_description: form.short_description || null,
    synonyms: listFromLines(form.synonyms),
    is_indexable: form.is_indexable,
    sort_order: Number(form.sort_order || 0),
  };
}
