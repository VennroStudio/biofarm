import { useState } from 'react';
import { useDocumentTitle } from '@/hooks/useDocumentTitle';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { ShoppingCart, Minus, Plus, Trash2, ArrowRight, ShoppingBag } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { useCart } from '@/hooks/useCart';
import { useAuthContext } from '@/contexts/AuthContext';

const Cart = () => {
  useDocumentTitle('Корзина');
  const navigate = useNavigate();
  const { cart, total, updateQuantity, removeFromCart } = useCart();
  const { user } = useAuthContext();
  
  const deliveryCost = total >= 3000 ? 0 : 350;
  const finalTotal = total + deliveryCost;

  const handleCheckout = () => {
    navigate('/checkout');
  };

  if (cart.length === 0) {
    return (
      <div className="min-h-screen flex flex-col">
        <Header />
        <main className="flex-1 flex items-center justify-center bg-secondary/30 pt-24">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="text-center px-4"
          >
            <ShoppingBag className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
            <h1 className="text-2xl font-bold mb-2">Корзина пуста</h1>
            <p className="text-muted-foreground mb-6">
              Добавьте товары из каталога
            </p>
            <Button asChild size="lg">
              <Link to="/catalog">
                Перейти в каталог
                <ArrowRight className="ml-2 h-4 w-4" />
              </Link>
            </Button>
          </motion.div>
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      
      <main className="flex-1 pt-24 pb-8 md:pt-28 md:pb-12 bg-secondary/30">
        <div className="container px-4">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <h1 className="text-2xl md:text-3xl font-bold mb-8 flex items-center gap-3">
              <ShoppingCart className="h-8 w-8" />
              Корзина
              <span className="text-muted-foreground font-normal text-lg">
                ({cart.length} {cart.length === 1 ? 'товар' : 'товара'})
              </span>
            </h1>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              {/* Cart Items */}
              <div className="lg:col-span-2 space-y-4">
                {cart.map((item, index) => (
                  <motion.div
                    key={item.product.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                  >
                    <Card className="border-0 shadow-premium">
                      <CardContent className="p-4">
                        <div className="flex gap-4">
                          <Link to={`/product/${item.product.slug}`}>
                            <img
                              src={item.product.image}
                              alt={item.product.name}
                              className="w-24 h-24 object-cover rounded-lg"
                            />
                          </Link>
                          
                          <div className="flex-1 min-w-0">
                            <Link 
                              to={`/product/${item.product.slug}`}
                              className="font-medium hover:text-primary transition-colors line-clamp-2"
                            >
                              {item.product.name}
                            </Link>
                            <p className="text-sm text-muted-foreground mt-1">
                              {item.product.weight}
                            </p>
                            
                            <div className="flex items-center justify-between mt-3">
                              <div className="flex items-center gap-2">
                                <Button
                                  variant="outline"
                                  size="icon"
                                  className="h-8 w-8"
                                  onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
                                >
                                  <Minus className="h-3 w-3" />
                                </Button>
                                <span className="w-8 text-center font-medium">
                                  {item.quantity}
                                </span>
                                <Button
                                  variant="outline"
                                  size="icon"
                                  className="h-8 w-8"
                                  onClick={() => updateQuantity(item.product.id, item.quantity + 1)}
                                >
                                  <Plus className="h-3 w-3" />
                                </Button>
                              </div>
                              
                              <div className="flex items-center gap-4">
                                <div className="text-right">
                                  <p className="font-bold">
                                    {(item.product.price * item.quantity).toLocaleString()} ₽
                                  </p>
                                  {item.quantity > 1 && (
                                    <p className="text-sm text-muted-foreground">
                                      {item.product.price} ₽ × {item.quantity}
                                    </p>
                                  )}
                                </div>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                  onClick={() => removeFromCart(item.product.id)}
                                >
                                  <Trash2 className="h-4 w-4" />
                                </Button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </motion.div>
                ))}
              </div>

              {/* Order Summary */}
              <div className="lg:col-span-1">
                <Card className="border-0 shadow-premium-lg sticky top-24">
                  <CardHeader>
                    <CardTitle>Итого</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Товары ({cart.length})</span>
                      <span>{total.toLocaleString()} ₽</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Доставка</span>
                      <span className={deliveryCost === 0 ? 'text-green-600' : ''}>
                        {deliveryCost === 0 ? 'Бесплатно' : `${deliveryCost} ₽`}
                      </span>
                    </div>
                    {deliveryCost > 0 && (
                      <p className="text-sm text-muted-foreground bg-muted/50 p-2 rounded">
                        До бесплатной доставки: {(3000 - total).toLocaleString()} ₽
                      </p>
                    )}
                    {user && user.bonusBalance > 0 && (
                      <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">Доступно бонусов</span>
                        <span className="text-primary font-medium">{user.bonusBalance} ₽</span>
                      </div>
                    )}
                    <Separator />
                    <div className="flex justify-between text-lg font-bold">
                      <span>К оплате</span>
                      <span>{finalTotal.toLocaleString()} ₽</span>
                    </div>
                    <div className="text-sm text-green-600 bg-green-50 p-2 rounded">
                      + {Math.floor(total * 0.05)} бонусов за заказ
                    </div>
                  </CardContent>
                  <CardFooter className="flex-col gap-3">
                    <Button 
                      className="w-full" 
                      size="lg"
                      onClick={handleCheckout}
                    >
                      Оформить заказ
                      <ArrowRight className="ml-2 h-4 w-4" />
                    </Button>
                    <Button variant="outline" className="w-full" asChild>
                      <Link to="/catalog">Продолжить покупки</Link>
                    </Button>
                  </CardFooter>
                </Card>
              </div>
            </div>
          </motion.div>
        </div>
      </main>
      
      <Footer />
    </div>
  );
};

export default Cart;
