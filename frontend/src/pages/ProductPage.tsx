import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  ShoppingCart, 
  Heart, 
  Share2, 
  Check, 
  Minus, 
  Plus,
  ExternalLink,
  Truck,
  Shield,
  RotateCcw
} from 'lucide-react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { getProductBySlug, getProducts, Product } from '@/data/products';
import { ProductCard } from '@/components/catalog/ProductCard';
import { ProductGallery } from '@/components/product/ProductGallery';
import { useCart } from '@/hooks/useCart';
import { toast } from 'sonner';

const ProductPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [product, setProduct] = useState<Product | null>(null);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const { addToCart } = useCart();

  useEffect(() => {
    if (!slug) {
      setLoading(false);
      return;
    }

    Promise.all([
      getProductBySlug(slug),
      getProducts()
    ])
      .then(([productData, allProducts]) => {
        if (productData) {
          setProduct(productData);
          setRelatedProducts(
            allProducts.filter((p) => p.category === productData.category && p.id !== productData.id).slice(0, 4)
          );
        }
      })
      .catch((error) => {
        console.error('Failed to load product:', error);
      })
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <div className="container mx-auto px-4 py-32 text-center">
          <p className="text-muted-foreground">Загрузка товара...</p>
        </div>
        <Footer />
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-background">
        <Header />
        <div className="container mx-auto px-4 py-32 text-center">
          <h1 className="text-3xl font-bold text-foreground mb-4">Товар не найден</h1>
          <p className="text-muted-foreground mb-8">К сожалению, такого товара не существует</p>
          <Link to="/catalog">
            <Button className="gradient-primary">Вернуться в каталог</Button>
          </Link>
        </div>
        <Footer />
      </div>
    );
  }

  // Главное изображение всегда первое, затем дополнительные
  const images = product.images && product.images.length > 0
    ? [product.image, ...product.images]
    : [product.image];

  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      {/* Breadcrumb */}
      <div className="pt-24 pb-4 bg-muted/30">
        <div className="container mx-auto px-4">
          <nav className="flex items-center gap-2 text-sm text-muted-foreground">
            <Link to="/" className="hover:text-primary transition-colors">Главная</Link>
            <span>/</span>
            <Link to="/catalog" className="hover:text-primary transition-colors">Каталог</Link>
            <span>/</span>
            <span className="text-foreground">{product.name}</span>
          </nav>
        </div>
      </div>

      {/* Product Section */}
      <section className="py-12">
        <div className="container mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-12">
            {/* Product Images with Gallery */}
            <motion.div
              initial={{ opacity: 0, x: -30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
            >
              <ProductGallery 
                images={images} 
                productName={product.name} 
                badge={product.badge} 
              />
            </motion.div>

            {/* Product Info */}
            <motion.div
              initial={{ opacity: 0, x: 30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
            >
              <div className="sticky top-28">
                <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-4">
                  {product.name}
                </h1>
                
                {product.shortDescription && (
                  <p className="text-muted-foreground text-lg mb-6">
                    {product.shortDescription}
                  </p>
                )}

                {/* Weight and Ingredients - moved above price */}
                <div className="mb-6 space-y-2">
                  <span className="text-accent font-medium block">{product.weight}</span>
                  {product.ingredients && (
                    <p className="text-sm text-muted-foreground">Состав: {product.ingredients}</p>
                  )}
                </div>

                {/* Price */}
                <div className="flex items-center gap-4 mb-6">
                  <span className="text-4xl font-bold text-primary">
                    {product.price} ₽
                  </span>
                  {product.oldPrice && (
                    <span className="text-xl text-muted-foreground line-through">
                      {product.oldPrice} ₽
                    </span>
                  )}
                  {product.oldPrice && (
                    <Badge variant="secondary" className="bg-red-100 text-red-600">
                      -{Math.round((1 - product.price / product.oldPrice) * 100)}%
                    </Badge>
                  )}
                </div>

                {/* Quantity */}
                <div className="flex items-center gap-4 mb-6">
                  <span className="text-muted-foreground">Количество:</span>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    <span className="w-12 text-center font-semibold">{quantity}</span>
                    <Button
                      variant="outline"
                      size="icon"
                      onClick={() => setQuantity(quantity + 1)}
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                  </div>
                </div>

                {/* Add to Cart */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                  <Button 
                    size="lg" 
                    className="flex-1 gradient-primary text-primary-foreground"
                    onClick={() => {
                      addToCart(product, quantity);
                      toast.success(`${product.name} добавлен в корзину`);
                    }}
                  >
                    <ShoppingCart className="mr-2 h-5 w-5" />
                    Добавить в корзину — {product.price * quantity} ₽
                  </Button>
                  <Button size="lg" variant="outline" className="gap-2">
                    <Heart className="h-5 w-5" />
                  </Button>
                  <Button size="lg" variant="outline" className="gap-2">
                    <Share2 className="h-5 w-5" />
                  </Button>
                </div>

                {/* Marketplace Links */}
                <div className="flex flex-col sm:flex-row gap-3 mb-8">
                  {product.wbLink && (
                    <a href={product.wbLink} target="_blank" rel="noopener noreferrer" className="flex-1">
                      <Button variant="outline" className="w-full gap-2 border-purple-300 text-purple-600 hover:bg-purple-50">
                        <ExternalLink className="h-4 w-4" />
                        Купить на Wildberries
                      </Button>
                    </a>
                  )}
                  {product.ozonLink && (
                    <a href={product.ozonLink} target="_blank" rel="noopener noreferrer" className="flex-1">
                      <Button variant="outline" className="w-full gap-2 border-blue-300 text-blue-600 hover:bg-blue-50">
                        <ExternalLink className="h-4 w-4" />
                        Купить на Ozon
                      </Button>
                    </a>
                  )}
                </div>

                <Separator className="my-6" />

                {/* Features */}
                {product.features && (
                  <div className="mb-6">
                    <h3 className="font-semibold text-foreground mb-3">Особенности:</h3>
                    <ul className="space-y-2">
                      {product.features.map((feature, index) => (
                        <li key={index} className="flex items-center gap-2 text-muted-foreground">
                          <Check className="h-4 w-4 text-primary" />
                          {feature}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                {/* Benefits */}
                <div className="grid grid-cols-3 gap-4 mt-8">
                  <div className="text-center p-4 bg-muted/50 rounded-xl">
                    <Truck className="h-6 w-6 mx-auto mb-2 text-primary" />
                    <span className="text-sm text-muted-foreground">Быстрая доставка</span>
                  </div>
                  <div className="text-center p-4 bg-muted/50 rounded-xl">
                    <Shield className="h-6 w-6 mx-auto mb-2 text-primary" />
                    <span className="text-sm text-muted-foreground">Гарантия качества</span>
                  </div>
                  <div className="text-center p-4 bg-muted/50 rounded-xl">
                    <RotateCcw className="h-6 w-6 mx-auto mb-2 text-primary" />
                    <span className="text-sm text-muted-foreground">Возврат 14 дней</span>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>

          {/* Description & Ingredients */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="mt-16"
          >
            <h2 className="text-2xl font-display font-bold text-foreground mb-6">Описание</h2>
            <div 
              className="prose prose-lg max-w-none
                prose-headings:font-display prose-headings:text-foreground prose-headings:font-bold
                prose-h2:text-2xl prose-h2:mt-12 prose-h2:mb-6
                prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-4
                prose-p:text-muted-foreground prose-p:leading-relaxed prose-p:mb-6
                prose-strong:text-foreground prose-strong:font-semibold
                prose-ul:text-muted-foreground prose-ul:my-6
                prose-li:my-2
                prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                prose-ol:text-muted-foreground prose-ol:my-6
                prose-blockquote:text-muted-foreground prose-blockquote:border-l-primary
                mb-8"
              dangerouslySetInnerHTML={{ __html: product.description }}
            />
          </motion.div>
        </div>
      </section>

      {/* Related Products */}
      {relatedProducts.length > 0 && (
        <section className="py-16 bg-muted/30">
          <div className="container mx-auto px-4">
            <h2 className="text-2xl font-display font-bold text-foreground mb-8">
              Похожие товары
            </h2>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {relatedProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        </section>
      )}

      <Footer />
    </div>
  );
};

export default ProductPage;
