import { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { Menu, X, ShoppingCart, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useCart } from '@/hooks/useCart';
import { useAuthContext } from '@/contexts/AuthContext';

const navLinks = [
  { href: '/#partner', label: 'Сотрудничество' },
  { href: '/#catalog', label: 'Каталог' },
  { href: '/#blog', label: 'Блог' },
  { href: '/#about', label: 'О нас' },
  { href: '/#reviews', label: 'Отзывы' },
  { href: '/#contacts', label: 'Контакты' },
];

export const Header = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const { itemCount: cartCount } = useCart();
  const { isAuthenticated } = useAuthContext();

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled
          ? 'bg-background/95 backdrop-blur-md shadow-premium'
          : 'bg-primary/90 backdrop-blur-sm'
      }`}
    >
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-20">
          {/* Logo */}
          <Link to="/" className="flex items-center gap-2">
            <span className={`text-2xl font-display font-bold transition-colors ${
              isScrolled ? 'text-primary' : 'text-white'
            }`}>
              БИОФАРМ
            </span>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden lg:flex items-center gap-8">
            {navLinks.map((link) => (
              <a
                key={link.href}
                href={link.href}
                className={`text-sm font-medium transition-colors hover:text-accent ${
                  isScrolled ? 'text-foreground' : 'text-white/90'
                }`}
              >
                {link.label}
              </a>
            ))}
          </nav>

          {/* Actions */}
          <div className="hidden lg:flex items-center gap-4">
            <Link to="/cart">
              <Button
                variant="ghost"
                size="icon"
                className={`relative ${isScrolled ? 'text-foreground' : 'text-white'}`}
              >
                <ShoppingCart className="h-5 w-5" />
                {cartCount > 0 && (
                  <Badge 
                    className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                    variant="destructive"
                  >
                    {cartCount}
                  </Badge>
                )}
              </Button>
            </Link>
            <Link to={isAuthenticated ? "/profile" : "/login"}>
              <Button
                variant="ghost"
                size="icon"
                className={isScrolled ? 'text-foreground' : 'text-white'}
              >
                <User className="h-5 w-5" />
              </Button>
            </Link>
            <Button asChild className="gradient-primary text-primary-foreground">
              <Link to="/catalog">Заказать</Link>
            </Button>
          </div>

          {/* Mobile Menu Button */}
          <button
            className="lg:hidden p-2"
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          >
            {isMobileMenuOpen ? (
              <X className={`h-6 w-6 ${isScrolled ? 'text-foreground' : 'text-white'}`} />
            ) : (
              <Menu className={`h-6 w-6 ${isScrolled ? 'text-foreground' : 'text-white'}`} />
            )}
          </button>
        </div>
      </div>

      {/* Mobile Menu */}
      <AnimatePresence>
        {isMobileMenuOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            className="lg:hidden bg-background border-t border-border"
          >
            <nav className="container mx-auto px-4 py-6 flex flex-col gap-4">
              {navLinks.map((link) => (
                <a
                  key={link.href}
                  href={link.href}
                  className="text-foreground text-lg font-medium py-2 hover:text-primary transition-colors"
                  onClick={() => setIsMobileMenuOpen(false)}
                >
                  {link.label}
                </a>
              ))}
              <div className="flex gap-4 pt-4 border-t border-border">
                <Button asChild variant="outline" className="flex-1">
                  <Link to={isAuthenticated ? "/profile" : "/login"} onClick={() => setIsMobileMenuOpen(false)}>
                    <User className="h-4 w-4 mr-2" />
                    {isAuthenticated ? 'Профиль' : 'Войти'}
                  </Link>
                </Button>
                <Button asChild className="flex-1 gradient-primary">
                  <Link to="/cart" onClick={() => setIsMobileMenuOpen(false)}>
                    <ShoppingCart className="h-4 w-4 mr-2" />
                    Корзина {cartCount > 0 && `(${cartCount})`}
                  </Link>
                </Button>
              </div>
            </nav>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
};
