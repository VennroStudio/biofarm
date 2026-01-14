import blogData from './blog.json';

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

// Transform JSON data to match the expected interface
export const blogPosts: BlogPost[] = blogData.posts.map(post => {
  const author = blogData.authors.find(a => a.id === post.author_id) || blogData.authors[0];
  const category = blogData.categories.find(c => c.id === post.category_id);
  
  return {
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
    category: category?.label || post.category_id,
    author: {
      name: author.name,
      avatar: author.avatar,
    },
    readTime: `${post.read_time} мин`,
  };
});

export const categories = ['Все', ...blogData.categories.filter(c => c.id !== 'all').map(c => c.label)];
