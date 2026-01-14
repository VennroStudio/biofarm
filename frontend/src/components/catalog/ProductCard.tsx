import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ShoppingCart, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useCart } from '@/hooks/useCart';
import { toast } from 'sonner';
import type { Product } from '@/data/products';

interface ProductCardProps {
  product: Product;
  index?: number;
  viewMode?: 'grid' | 'list';
}

const itemVariants = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.5 } },
};

export const ProductCard = ({ product, index = 0, viewMode = 'grid' }: ProductCardProps) => {
  const { addToCart } = useCart();

  const handleAddToCart = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    addToCart(product, 1);
    toast.success(`${product.name} добавлен в корзину`);
  };

  if (viewMode === 'list') {
    return (
      <motion.div
        variants={itemVariants}
        layout
        className="group bg-card rounded-2xl overflow-hidden shadow-premium hover:shadow-premium-lg transition-all duration-300"
      >
        <Link to={`/product/${product.slug}`} className="flex gap-4 p-4">
          <div className="relative w-24 h-24 flex-shrink-0 overflow-hidden rounded-lg">
            <img
              src={product.image}
              alt={product.name}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            {product.badge && (
              <Badge className="absolute top-1 left-1 bg-accent text-accent-foreground text-xs px-1.5 py-0.5">
                {product.badge}
              </Badge>
            )}
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between mb-1">
              <h3 className="text-lg font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1">
                {product.name}
              </h3>
              <span className="text-sm text-muted-foreground ml-2 flex-shrink-0">{product.weight}</span>
            </div>
            <p className="text-sm text-muted-foreground mb-2 line-clamp-1">
              {product.shortDescription}
            </p>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="text-xl font-bold text-primary">
                  {product.price} ₽
                </span>
                {product.oldPrice && (
                  <span className="text-muted-foreground line-through text-sm">
                    {product.oldPrice} ₽
                  </span>
                )}
              </div>
              <Button 
                onClick={handleAddToCart}
                size="sm"
                className="gradient-primary text-primary-foreground"
              >
                <ShoppingCart className="mr-1 h-4 w-4" />
                В корзину
              </Button>
            </div>
          </div>
        </Link>
      </motion.div>
    );
  }

  return (
    <motion.div
      variants={itemVariants}
      layout
      className="group bg-card rounded-2xl overflow-hidden shadow-premium hover:shadow-premium-lg transition-all duration-300"
    >
      <Link to={`/product/${product.slug}`}>
        <div className="relative aspect-square overflow-hidden">
          <img
            src={product.image}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          {product.badge && (
            <Badge className="absolute top-4 left-4 bg-accent text-accent-foreground">
              {product.badge}
            </Badge>
          )}
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
            <div className="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <Button variant="secondary" size="sm" className="gap-2">
                <Eye className="h-4 w-4" />
                Подробнее
              </Button>
            </div>
          </div>
        </div>
      </Link>
      <div className="p-6">
        <div className="flex items-start justify-between mb-2">
          <Link to={`/product/${product.slug}`}>
            <h3 className="text-lg font-bold text-foreground group-hover:text-primary transition-colors">
              {product.name}
            </h3>
          </Link>
          <span className="text-sm text-muted-foreground">{product.weight}</span>
        </div>
        <p className="text-sm text-muted-foreground mb-4 line-clamp-2">
          {product.shortDescription}
        </p>
        <div className="flex items-center gap-2 mb-4">
          <span className="text-2xl font-bold text-primary">
            {product.price} ₽
          </span>
          {product.oldPrice && (
            <span className="text-muted-foreground line-through">
              {product.oldPrice} ₽
            </span>
          )}
        </div>
        <div className="flex gap-2">
          <Button 
            onClick={handleAddToCart}
            className="flex-1 gradient-primary text-primary-foreground"
          >
            <ShoppingCart className="mr-2 h-4 w-4" />
            В корзину
          </Button>
        </div>
      </div>
    </motion.div>
  );
};
