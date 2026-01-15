import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { Calendar, Clock, ArrowRight, Search } from 'lucide-react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { getBlogPosts, categories, BlogPost } from '@/data/blogPosts';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { 
      staggerChildren: 0.15,
      delayChildren: 0.9, // Задержка перед началом анимации детей (после featured post)
    },
  },
};

const itemVariants = {
  hidden: { opacity: 0, y: 30 },
  visible: { 
    opacity: 1, 
    y: 0, 
    transition: { 
      duration: 0.6,
      ease: "easeOut"
    } 
  },
};

const POSTS_PER_PAGE = 9;

const Blog = () => {
  useDocumentTitle('Блог');
  const [selectedCategory, setSelectedCategory] = useState('Все');
  const [searchQuery, setSearchQuery] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [blogPosts, setBlogPosts] = useState<BlogPost[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getBlogPosts()
      .then(setBlogPosts)
      .catch((error) => {
        console.error('Failed to load blog posts:', error);
      })
      .finally(() => setLoading(false));
  }, []);

  const filteredPosts = blogPosts.filter((post) => {
    const matchesCategory = selectedCategory === 'Все' || post.category === selectedCategory;
    const matchesSearch = post.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          post.excerpt.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  // Pagination
  const totalPages = Math.ceil(filteredPosts.length / POSTS_PER_PAGE);
  const paginatedPosts = filteredPosts.slice(
    (currentPage - 1) * POSTS_PER_PAGE,
    currentPage * POSTS_PER_PAGE
  );

  const featuredPost = currentPage === 1 ? paginatedPosts[0] : null;
  const otherPosts = currentPage === 1 ? paginatedPosts.slice(1) : paginatedPosts;

  const handleCategoryChange = (category: string) => {
    setSelectedCategory(category);
    setCurrentPage(1);
  };

  const handleSearchChange = (query: string) => {
    setSearchQuery(query);
    setCurrentPage(1);
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      <main className="pt-32 pb-20">
        <div className="container mx-auto px-4">
          {/* Header */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-center mb-12"
          >
            <span className="inline-block text-accent font-medium mb-4">
              Полезные статьи
            </span>
            <h1 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-4">
              Блог BioFarm
            </h1>
            <p className="text-muted-foreground text-lg max-w-2xl mx-auto">
              Советы экспертов, рецепты и интересные факты о натуральных продуктах
            </p>
          </motion.div>

          {/* Filters */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.2 }}
            className="flex flex-col md:flex-row gap-4 mb-12"
          >
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
              <Input
                type="text"
                placeholder="Поиск статей..."
                value={searchQuery}
                onChange={(e) => handleSearchChange(e.target.value)}
                className="pl-10"
              />
            </div>
            <div className="flex flex-wrap gap-2">
              {categories.map((category) => (
                <Button
                  key={category}
                  variant={selectedCategory === category ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => handleCategoryChange(category)}
                  className={selectedCategory === category ? 'gradient-primary' : ''}
                >
                  {category}
                </Button>
              ))}
            </div>
          </motion.div>

          {filteredPosts.length === 0 ? (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              className="text-center py-20"
            >
              <p className="text-muted-foreground text-lg">
                Статьи не найдены. Попробуйте изменить параметры поиска.
              </p>
            </motion.div>
          ) : (
            <>
              {/* Featured Post */}
              {featuredPost && (
                <motion.article
                  initial={{ opacity: 0, y: 30 }}
                  animate={!loading ? { opacity: 1, y: 0 } : { opacity: 0, y: 30 }}
                  transition={{ duration: 0.6, delay: 0.2 }}
                  className="mb-16"
                >
                  <Link to={`/blog/${featuredPost.slug}`} className="group block">
                    <div className="grid lg:grid-cols-2 gap-8 bg-card rounded-3xl overflow-hidden shadow-premium hover:shadow-premium-lg transition-all duration-300">
                      <div className="relative aspect-[4/3] lg:aspect-auto overflow-hidden">
                        <img
                          src={featuredPost.image}
                          alt={featuredPost.title}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        />
                        <span className="absolute top-6 left-6 px-4 py-2 bg-accent text-accent-foreground font-medium rounded-full">
                          {featuredPost.category}
                        </span>
                      </div>
                      <div className="p-8 lg:p-12 flex flex-col justify-center">
                        <div className="flex items-center gap-4 text-muted-foreground text-sm mb-4">
                          <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            {featuredPost.date}
                          </div>
                          <div className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            {featuredPost.readTime}
                          </div>
                        </div>
                        <h2 className="text-2xl md:text-3xl font-display font-bold text-foreground mb-4 group-hover:text-primary transition-colors">
                          {featuredPost.title}
                        </h2>
                        <p className="text-muted-foreground text-lg mb-6 line-clamp-3">
                          {featuredPost.excerpt}
                        </p>
                        <div className="flex items-center gap-4">
                          <img
                            src={featuredPost.author.avatar}
                            alt={featuredPost.author.name}
                            className="w-12 h-12 rounded-full object-cover"
                          />
                          <span className="font-medium text-foreground">
                            {featuredPost.author.name}
                          </span>
                        </div>
                      </div>
                    </div>
                  </Link>
                </motion.article>
              )}

              {/* Other Posts Grid */}
              <motion.div
                variants={containerVariants}
                initial="hidden"
                animate={!loading ? 'visible' : 'hidden'}
                className="grid md:grid-cols-2 lg:grid-cols-3 gap-8"
              >
                {otherPosts.map((post) => (
                  <motion.article key={post.id} variants={itemVariants}>
                    <Link to={`/blog/${post.slug}`} className="group block h-full">
                      <div className="bg-card rounded-2xl overflow-hidden shadow-premium hover:shadow-premium-lg transition-all duration-300 h-full flex flex-col">
                        <div className="relative aspect-[4/3] overflow-hidden">
                          <img
                            src={post.image}
                            alt={post.title}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                          />
                          <span className="absolute top-4 left-4 px-3 py-1 bg-accent text-accent-foreground text-sm font-medium rounded-full">
                            {post.category}
                          </span>
                        </div>
                        <div className="p-6 flex flex-col flex-1">
                          <div className="flex items-center gap-4 text-muted-foreground text-sm mb-3">
                            <div className="flex items-center gap-1">
                              <Calendar className="h-4 w-4" />
                              {post.date}
                            </div>
                            <div className="flex items-center gap-1">
                              <Clock className="h-4 w-4" />
                              {post.readTime}
                            </div>
                          </div>
                          <h3 className="text-lg font-bold text-foreground mb-3 group-hover:text-primary transition-colors line-clamp-2">
                            {post.title}
                          </h3>
                          <p className="text-muted-foreground text-sm line-clamp-2 mb-4 flex-1">
                            {post.excerpt}
                          </p>
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                              <img
                                src={post.author.avatar}
                                alt={post.author.name}
                                className="w-8 h-8 rounded-full object-cover"
                              />
                              <span className="text-sm text-muted-foreground">
                                {post.author.name}
                              </span>
                            </div>
                            <ArrowRight className="h-5 w-5 text-primary opacity-0 group-hover:opacity-100 transition-opacity" />
                          </div>
                        </div>
                      </div>
                    </Link>
                  </motion.article>
                ))}
              </motion.div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="mt-12">
                  <Pagination>
                    <PaginationContent>
                      <PaginationItem>
                        <PaginationPrevious 
                          onClick={() => setCurrentPage(p => Math.max(1, p - 1))} 
                          className={currentPage === 1 ? 'pointer-events-none opacity-50' : 'cursor-pointer'} 
                        />
                      </PaginationItem>
                      {Array.from({ length: totalPages }, (_, i) => i + 1)
                        .filter(page => page === 1 || page === totalPages || Math.abs(page - currentPage) <= 2)
                        .map((page, idx, arr) => (
                          <PaginationItem key={page}>
                            {idx > 0 && arr[idx - 1] !== page - 1 && (
                              <span className="px-2">...</span>
                            )}
                            <PaginationLink 
                              onClick={() => setCurrentPage(page)} 
                              isActive={currentPage === page} 
                              className="cursor-pointer"
                            >
                              {page}
                            </PaginationLink>
                          </PaginationItem>
                        ))}
                      <PaginationItem>
                        <PaginationNext 
                          onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))} 
                          className={currentPage === totalPages ? 'pointer-events-none opacity-50' : 'cursor-pointer'} 
                        />
                      </PaginationItem>
                    </PaginationContent>
                  </Pagination>
                </div>
              )}
            </>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
};

export default Blog;
