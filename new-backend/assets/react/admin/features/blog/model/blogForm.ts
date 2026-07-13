import type { BlogPost } from '../../../types';

export type BlogForm = {
  id?: number;
  title: string;
  slug: string;
  h1: string;
  seo_title: string;
  seo_description: string;
  excerpt: string;
  content: string;
  image: string;
  image_alt: string;
  category_id: string;
  author_name: string;
  read_time: string;
  is_published: boolean;
  published_at: string;
};

export const emptyBlogForm: BlogForm = {
  title: '',
  slug: '',
  h1: '',
  seo_title: '',
  seo_description: '',
  excerpt: '',
  content: '',
  image: '',
  image_alt: '',
  category_id: 'health',
  author_name: 'Редакция BioFarm',
  read_time: '5',
  is_published: true,
  published_at: '',
};

export function blogFormFromPost(post: BlogPost): BlogForm {
  return {
    id: post.id,
    title: post.title,
    slug: post.slug,
    h1: post.h1 ?? '',
    seo_title: post.seo_title ?? '',
    seo_description: post.seo_description ?? '',
    excerpt: post.excerpt,
    content: post.content,
    image: post.image,
    image_alt: post.image_alt ?? '',
    category_id: post.category_id,
    author_name: post.author_name,
    read_time: String(post.read_time),
    is_published: post.is_published,
    published_at: post.published_at ? post.published_at.replace(' ', 'T').slice(0, 16) : '',
  };
}

export function blogPayloadFromForm(form: BlogForm) {
  return {
    title: form.title,
    slug: form.slug || null,
    h1: form.h1 || null,
    seoTitle: form.seo_title || null,
    seoDescription: form.seo_description || null,
    excerpt: form.excerpt,
    content: form.content,
    image: form.image,
    imageAlt: form.image_alt || null,
    categoryId: form.category_id,
    authorName: form.author_name,
    readTime: Number(form.read_time),
    isPublished: form.is_published,
    publishedAt: form.published_at || null,
  };
}
