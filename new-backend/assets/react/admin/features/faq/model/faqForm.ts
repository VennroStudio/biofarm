import type { FaqItem } from '../../../types';

export type FaqForm = {
  id?: number;
  question: string;
  answer: string;
  page_scope: string;
  page_id: string;
  is_active: boolean;
  sort_order: number;
};

export const faqScopes = [
  { value: 'global', label: 'Все страницы' },
  { value: 'home', label: 'Главная' },
  { value: 'catalog', label: 'Каталог' },
  { value: 'category', label: 'Категория' },
  { value: 'attribute', label: 'SEO-фильтр' },
  { value: 'product', label: 'Товар' },
  { value: 'blog', label: 'Блог' },
  { value: 'post', label: 'Статья' },
  { value: 'page', label: 'Обычная страница' },
  { value: 'certificates', label: 'Сертификаты' },
];

export const emptyFaqForm: FaqForm = {
  question: '',
  answer: '',
  page_scope: 'global',
  page_id: '',
  is_active: true,
  sort_order: 0,
};

export function faqFormFromItem(item: FaqItem): FaqForm {
  return {
    id: item.id,
    question: item.question,
    answer: item.answer,
    page_scope: item.page_scope,
    page_id: item.page_id || '',
    is_active: item.is_active,
    sort_order: item.sort_order,
  };
}

export function faqPayloadFromForm(form: FaqForm) {
  return {
    question: form.question,
    answer: form.answer,
    page_scope: form.page_scope,
    page_id: form.page_id || null,
    is_active: form.is_active,
    sort_order: form.sort_order,
  };
}

export function faqScopeLabel(scope: string) {
  return faqScopes.find((item) => item.value === scope)?.label || scope;
}
