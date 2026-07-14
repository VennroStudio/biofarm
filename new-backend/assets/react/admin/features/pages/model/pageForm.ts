import type { CmsPage } from '../../../types';

export type PageForm = {
  id?: number;
  page_type: 'system' | 'custom';
  system_key: string;
  slug_path: string;
  template: string;
  title: string;
  h1: string;
  content: string;
  excerpt: string;
  seo_title: string;
  seo_description: string;
  og_title: string;
  og_description: string;
  og_image: string;
  og_image_alt: string;
  is_published: boolean;
  is_indexable: boolean;
  show_in_sitemap: boolean;
  show_in_header: boolean;
  show_in_footer: boolean;
  sort_order: string;
  published_at: string;
};

export const emptyPageForm: PageForm = {
  page_type: 'custom',
  system_key: '',
  slug_path: '',
  template: 'basic',
  title: '',
  h1: '',
  content: '',
  excerpt: '',
  seo_title: '',
  seo_description: '',
  og_title: '',
  og_description: '',
  og_image: '',
  og_image_alt: '',
  is_published: true,
  is_indexable: true,
  show_in_sitemap: true,
  show_in_header: false,
  show_in_footer: false,
  sort_order: '0',
  published_at: '',
};

export function pageFormFromPage(page: CmsPage): PageForm {
  return {
    id: page.id,
    page_type: page.page_type,
    system_key: page.system_key ?? '',
    slug_path: page.slug_path ?? '',
    template: page.template ?? 'basic',
    title: page.title,
    h1: page.h1 ?? '',
    content: page.content ?? '',
    excerpt: page.excerpt ?? '',
    seo_title: page.seo_title ?? '',
    seo_description: page.seo_description ?? '',
    og_title: page.og_title ?? '',
    og_description: page.og_description ?? '',
    og_image: page.og_image ?? '',
    og_image_alt: page.og_image_alt ?? '',
    is_published: page.is_published,
    is_indexable: page.is_indexable,
    show_in_sitemap: page.show_in_sitemap,
    show_in_header: page.show_in_header,
    show_in_footer: page.show_in_footer,
    sort_order: String(page.sort_order),
    published_at: page.published_at ? page.published_at.replace(' ', 'T').slice(0, 16) : '',
  };
}

export function pagePayloadFromForm(form: PageForm) {
  return {
    title: form.title,
    slugPath: form.page_type === 'custom' ? form.slug_path : null,
    template: form.page_type === 'custom' ? form.template : null,
    h1: form.h1 || null,
    content: form.page_type === 'custom' ? form.content || null : null,
    excerpt: form.page_type === 'custom' ? form.excerpt || null : null,
    seoTitle: form.seo_title || null,
    seoDescription: form.seo_description || null,
    ogTitle: form.og_title || null,
    ogDescription: form.og_description || null,
    ogImage: form.og_image || null,
    ogImageAlt: form.og_image_alt || null,
    publishedAt: form.page_type === 'custom' ? form.published_at || null : null,
    isPublished: form.is_published,
    isIndexable: form.is_indexable,
    showInSitemap: form.show_in_sitemap,
    showInHeader: form.show_in_header,
    showInFooter: form.show_in_footer,
    sortOrder: form.sort_order ? Number(form.sort_order) : 0,
  };
}
