import type { BlogPost } from '../../../types';

export type BlogForm = {
  id?: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  image: string;
  category_id: string;
  author_name: string;
  read_time: string;
  is_published: boolean;
};

export const emptyBlogForm: BlogForm = {
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  image: '',
  category_id: 'health',
  author_name: 'Редакция BioFarm',
  read_time: '5',
  is_published: true,
};

export function blogFormFromPost(post: BlogPost): BlogForm {
  return {
    id: post.id,
    title: post.title,
    slug: post.slug,
    excerpt: post.excerpt,
    content: post.content,
    image: post.image,
    category_id: post.category_id,
    author_name: post.author_name,
    read_time: String(post.read_time),
    is_published: post.is_published,
  };
}

export function blogPayloadFromForm(form: BlogForm) {
  return {
    title: form.title,
    slug: form.slug || null,
    excerpt: form.excerpt,
    content: form.content,
    image: form.image,
    categoryId: form.category_id,
    authorName: form.author_name,
    readTime: Number(form.read_time),
    isPublished: form.is_published,
  };
}
