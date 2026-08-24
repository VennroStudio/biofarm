import type { PromoCode } from '../../../types';

export type PromoCodeForm = {
  id?: number;
  code: string;
  type: 'percent' | 'fixed';
  value: number;
  min_order_total: number;
  starts_at: string;
  ends_at: string;
  usage_limit: string;
  is_active: boolean;
};

export const emptyPromoCodeForm: PromoCodeForm = {
  code: '',
  type: 'percent',
  value: 10,
  min_order_total: 0,
  starts_at: '',
  ends_at: '',
  usage_limit: '',
  is_active: true,
};

export function promoCodeFormFromPromoCode(promoCode: PromoCode): PromoCodeForm {
  return {
    id: promoCode.id,
    code: promoCode.code,
    type: promoCode.type,
    value: promoCode.value,
    min_order_total: promoCode.min_order_total,
    starts_at: toInputDateTime(promoCode.starts_at),
    ends_at: toInputDateTime(promoCode.ends_at),
    usage_limit: promoCode.usage_limit ? String(promoCode.usage_limit) : '',
    is_active: promoCode.is_active,
  };
}

export function promoCodePayloadFromForm(form: PromoCodeForm) {
  return {
    code: form.code,
    type: form.type,
    value: form.value,
    min_order_total: form.min_order_total,
    starts_at: form.starts_at || null,
    ends_at: form.ends_at || null,
    usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
    is_active: form.is_active,
  };
}

function toInputDateTime(value: string | null) {
  if (!value) {
    return '';
  }

  return value.replace(' ', 'T').slice(0, 16);
}
