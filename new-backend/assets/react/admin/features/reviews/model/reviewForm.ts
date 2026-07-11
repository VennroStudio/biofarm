import type { Review } from '../../../types';

export type ReviewForm = {
  id?: string;
  product_id: string;
  user_name: string;
  rating: number;
  text: string;
  source: string;
  user_id: string;
  images: string[];
  is_approved: boolean;
};

export const emptyReviewForm: ReviewForm = {
  product_id: '',
  user_name: '',
  rating: 5,
  text: '',
  source: 'site',
  user_id: '',
  images: [],
  is_approved: true,
};

export const reviewSourceLabels: Record<string, string> = {
  site: 'Сайт',
  wildberries: 'WB',
  wb: 'WB',
  ozon: 'Ozon',
};

export function reviewFormFromReview(review: Review): ReviewForm {
  return {
    id: review.id,
    product_id: String(review.product_id),
    user_name: review.user_name,
    rating: review.rating,
    text: review.text,
    source: review.source || 'site',
    user_id: review.user_id ?? '',
    images: review.images ?? [],
    is_approved: review.is_approved,
  };
}

export function reviewPayloadFromForm(form: ReviewForm) {
  return {
    productId: Number(form.product_id),
    userName: form.user_name,
    rating: form.rating,
    text: form.text,
    source: form.source,
    userId: form.user_id || null,
    images: form.images,
    isApproved: form.is_approved,
  };
}
