import { api } from '@/lib/api';

export interface BlogPost {
  id: number;
  slug: string;
  title: string;
  excerpt: string;
  content: string;
  image: string;
  date: string;
  category: string;
  author: {
    name: string;
    avatar: string;
  };
  readTime: string;
}

let cachedPosts: BlogPost[] | null = null;

export const clearBlogPostsCache = () => {
  cachedPosts = null;
};

export const getBlogPosts = async (forceRefresh = false): Promise<BlogPost[]> => {
  if (cachedPosts && !forceRefresh) return cachedPosts;
  
  const data = await api.blog.getAll();
  cachedPosts = data.map((post: any) => ({
    id: post.id,
    slug: post.slug,
    title: post.title,
    excerpt: post.excerpt,
    content: post.content,
    image: post.image,
    date: new Date(post.date).toLocaleDateString('ru-RU', { 
      day: 'numeric', 
      month: 'long', 
      year: 'numeric' 
    }),
    category: post.category,
    author: {
      name: post.authorName || 'Автор',
      avatar: '',
    },
    readTime: `${post.readTime} мин`,
  }));
  
  return cachedPosts;
};

export const getBlogPostBySlug = async (slug: string): Promise<BlogPost | undefined> => {
  try {
    const data = await api.blog.getBySlug(slug);
    return {
      id: data.id,
      slug: data.slug,
      title: data.title,
      excerpt: data.excerpt,
      content: data.content,
      image: data.image,
      date: new Date(data.date).toLocaleDateString('ru-RU', { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
      }),
      category: data.category,
      author: {
        name: data.authorName || 'Автор',
        avatar: '',
      },
      readTime: `${data.readTime} мин`,
    };
  } catch {
    return undefined;
  }
};

export const categories = ['Все', 'Советы', 'Здоровье', 'О нас', 'Рецепты'];

// For backward compatibility
export const blogPosts: BlogPost[] = [];
