import { useState, useMemo, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Grid3X3, LayoutList, SlidersHorizontal } from 'lucide-react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ProductCard } from '@/components/catalog/ProductCard';
import { getProducts, Product } from '@/data/products';
import { getCategories, Category } from '@/data/categories';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.1 },
  },
};

type SortOption = 'default' | 'price-asc' | 'price-desc' | 'name';

const PRODUCTS_PER_PAGE = 12;

const Catalog = () => {
  useDocumentTitle('Каталог');
  const [activeCategory, setActiveCategory] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState<SortOption>('default');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [currentPage, setCurrentPage] = useState(1);
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      getProducts(),
      getCategories(true) // Только активные категории
    ])
      .then(([productsData, categoriesData]) => {
        setProducts(productsData);
        setCategories(categoriesData);
      })
      .catch((error) => {
        console.error('Failed to load data:', error);
      })
      .finally(() => setLoading(false));
  }, []);

  const filteredAndSortedProducts = useMemo(() => {
    let result = products;

    // Filter by category
    if (activeCategory !== 'all') {
      result = result.filter((p) => String(p.category) === activeCategory);
    }

    // Filter by search query
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      result = result.filter(
        (p) =>
          p.name.toLowerCase().includes(query) ||
          p.description.toLowerCase().includes(query)
      );
    }

    // Sort
    switch (sortBy) {
      case 'price-asc':
        result = [...result].sort((a, b) => a.price - b.price);
        break;
      case 'price-desc':
        result = [...result].sort((a, b) => b.price - a.price);
        break;
      case 'name':
        result = [...result].sort((a, b) => a.name.localeCompare(b.name));
        break;
      default:
        break;
    }

    return result;
  }, [products, activeCategory, searchQuery, sortBy]);

  // Pagination
  const totalPages = Math.ceil(filteredAndSortedProducts.length / PRODUCTS_PER_PAGE);
  const paginatedProducts = filteredAndSortedProducts.slice(
    (currentPage - 1) * PRODUCTS_PER_PAGE,
    currentPage * PRODUCTS_PER_PAGE
  );

  // Reset page when filters change
  const handleCategoryChange = (category: string) => {
    setActiveCategory(category);
    setCurrentPage(1);
  };

  const handleSearchChange = (query: string) => {
    setSearchQuery(query);
    setCurrentPage(1);
  };

  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      {/* Hero Banner */}
      <section className="pt-32 pb-16 bg-gradient-to-b from-primary/10 to-background">
        <div className="container mx-auto px-4 text-center">
          <motion.span
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="inline-block text-accent font-medium mb-4"
          >
            Натуральные продукты
          </motion.span>
          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="text-4xl md:text-5xl font-display font-bold text-foreground mb-6"
          >
            Каталог товаров
          </motion.h1>
          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="text-muted-foreground max-w-2xl mx-auto"
          >
            Экологически чистые продукты с собственных ферм. 
            Без пестицидов, без ГМО — только природа.
          </motion.p>
        </div>
      </section>

      {/* Filters and Products */}
      <section className="py-12">
        <div className="container mx-auto px-4">
          {/* Filters Bar */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="bg-card rounded-2xl p-6 mb-8 shadow-premium"
          >
            <div className="flex flex-col lg:flex-row gap-4 items-center justify-between">
              {/* Category Tabs */}
              <div className="flex flex-wrap gap-2">
                <Button
                  variant={activeCategory === 'all' ? 'default' : 'outline'}
                  onClick={() => handleCategoryChange('all')}
                  className={activeCategory === 'all' ? 'gradient-primary' : ''}
                  size="sm"
                >
                  Все товары
                </Button>
                {categories.map((category) => (
                  <Button
                    key={category.id}
                    variant={activeCategory === String(category.id) ? 'default' : 'outline'}
                    onClick={() => handleCategoryChange(String(category.id))}
                    className={activeCategory === String(category.id) ? 'gradient-primary' : ''}
                    size="sm"
                  >
                    {category.name}
                  </Button>
                ))}
              </div>

              {/* Search and Sort */}
              <div className="flex flex-wrap gap-3 items-center">
                <Input
                  placeholder="Поиск товаров..."
                  value={searchQuery}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  className="w-48"
                />
                <Select value={sortBy} onValueChange={(v) => setSortBy(v as SortOption)}>
                  <SelectTrigger className="w-44">
                    <SlidersHorizontal className="h-4 w-4 mr-2" />
                    <SelectValue placeholder="Сортировка" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="default">По умолчанию</SelectItem>
                    <SelectItem value="price-asc">Сначала дешёвые</SelectItem>
                    <SelectItem value="price-desc">Сначала дорогие</SelectItem>
                    <SelectItem value="name">По названию</SelectItem>
                  </SelectContent>
                </Select>
                <div className="flex gap-1">
                  <Button
                    variant={viewMode === 'grid' ? 'default' : 'outline'}
                    size="icon"
                    onClick={() => setViewMode('grid')}
                  >
                    <Grid3X3 className="h-4 w-4" />
                  </Button>
                  <Button
                    variant={viewMode === 'list' ? 'default' : 'outline'}
                    size="icon"
                    onClick={() => setViewMode('list')}
                  >
                    <LayoutList className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </motion.div>

          {/* Results Count */}
          <div className="mb-6">
            <p className="text-muted-foreground">
              Найдено товаров: <span className="font-semibold text-foreground">{filteredAndSortedProducts.length}</span>
            </p>
          </div>

          {/* Products Grid */}
          {loading ? (
            <div className="text-center py-12">
              <p className="text-muted-foreground">Загрузка товаров...</p>
            </div>
          ) : paginatedProducts.length > 0 ? (
            <>
              <motion.div
                variants={containerVariants}
                initial="hidden"
                animate="visible"
                className={
                  viewMode === 'grid'
                    ? 'grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
                    : 'flex flex-col gap-4'
                }
              >
                {paginatedProducts.map((product, index) => (
                  <ProductCard key={product.id} product={product} index={index} viewMode={viewMode} />
                ))}
              </motion.div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="mt-8">
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
          ) : (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              className="text-center py-16"
            >
              <p className="text-muted-foreground text-lg mb-4">
                Товары не найдены
              </p>
              <Button variant="outline" onClick={() => {
                setActiveCategory('all');
                setSearchQuery('');
                setCurrentPage(1);
              }}>
                Сбросить фильтры
              </Button>
            </motion.div>
          )}
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Catalog;
