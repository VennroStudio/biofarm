import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  CreditCard, Truck, MapPin, User, Phone, Mail, 
  CheckCircle, ArrowRight
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { Checkbox } from '@/components/ui/checkbox';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { useCart } from '@/hooks/useCart';
import { useAuthContext } from '@/contexts/AuthContext';
import { ordersApi, ShippingAddress } from '@/data/orders';
import { useToast } from '@/hooks/use-toast';

const Checkout = () => {
  const navigate = useNavigate();
  const { cart, total, clearCart } = useCart();
  const { user } = useAuthContext();
  const { toast } = useToast();
  
  const [isLoading, setIsLoading] = useState(false);
  const [useBonuses, setUseBonuses] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [deliveryMethod, setDeliveryMethod] = useState('cdek');
  
  const [form, setForm] = useState<ShippingAddress>({
    name: user?.name || '',
    phone: user?.phone || '',
    email: user?.email || '',
    city: '',
    address: '',
    postalCode: '',
    comment: '',
  });

  const deliveryCost = total >= 3000 ? 0 : 350;
  const bonusDiscount = useBonuses ? Math.min(user?.bonusBalance || 0, total * 0.3) : 0;
  const finalTotal = total + deliveryCost - bonusDiscount;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (cart.length === 0) {
      toast({
        title: 'Корзина пуста',
        variant: 'destructive',
      });
      return;
    }
    
    setIsLoading(true);
    
    try {
      const order = await ordersApi.createOrder(
        user?.id || 'guest',
        cart,
        form,
        paymentMethod === 'card' ? 'Банковская карта' : 'СБП',
        bonusDiscount
      );
      
      toast({
        title: 'Заказ оформлен!',
        description: `Номер заказа: ${order.id}`,
      });
      
      navigate('/order-success', { state: { orderId: order.id } });
    } catch {
      toast({
        title: 'Ошибка оформления',
        description: 'Попробуйте ещё раз',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (cart.length === 0) {
    navigate('/cart');
    return null;
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
            <h1 className="text-2xl md:text-3xl font-bold mb-8">Оформление заказа</h1>

            <form onSubmit={handleSubmit}>
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Form */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Contact Info */}
                  <Card className="border-0 shadow-premium">
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <User className="h-5 w-5" />
                        Контактные данные
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label htmlFor="name">Имя *</Label>
                          <Input
                            id="name"
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            required
                          />
                        </div>
                        <div className="space-y-2">
                          <Label htmlFor="phone">Телефон *</Label>
                          <div className="relative">
                            <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                              id="phone"
                              type="tel"
                              value={form.phone}
                              onChange={(e) => setForm({ ...form, phone: e.target.value })}
                              className="pl-10"
                              placeholder="+7 (999) 123-45-67"
                              required
                            />
                          </div>
                        </div>
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="email">Email *</Label>
                        <div className="relative">
                          <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                          <Input
                            id="email"
                            type="email"
                            value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                            className="pl-10"
                            required
                          />
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Delivery */}
                  <Card className="border-0 shadow-premium">
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <Truck className="h-5 w-5" />
                        Доставка
                      </CardTitle>
                      <CardDescription>
                        Выберите способ доставки
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <RadioGroup value={deliveryMethod} onValueChange={setDeliveryMethod}>
                        <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                          <RadioGroupItem value="cdek" id="cdek" />
                          <Label htmlFor="cdek" className="flex-1 cursor-pointer">
                            <span className="font-medium">СДЭК</span>
                            <span className="text-muted-foreground ml-2">от 2 дней</span>
                          </Label>
                          <span className="font-medium">{deliveryCost === 0 ? 'Бесплатно' : `${deliveryCost} ₽`}</span>
                        </div>
                        <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                          <RadioGroupItem value="post" id="post" />
                          <Label htmlFor="post" className="flex-1 cursor-pointer">
                            <span className="font-medium">Почта России</span>
                            <span className="text-muted-foreground ml-2">от 5 дней</span>
                          </Label>
                          <span className="font-medium">250 ₽</span>
                        </div>
                      </RadioGroup>
                    </CardContent>
                  </Card>

                  {/* Address */}
                  <Card className="border-0 shadow-premium">
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <MapPin className="h-5 w-5" />
                        Адрес доставки
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label htmlFor="city">Город *</Label>
                          <Input
                            id="city"
                            value={form.city}
                            onChange={(e) => setForm({ ...form, city: e.target.value })}
                            required
                          />
                        </div>
                        <div className="space-y-2">
                          <Label htmlFor="postalCode">Индекс *</Label>
                          <Input
                            id="postalCode"
                            value={form.postalCode}
                            onChange={(e) => setForm({ ...form, postalCode: e.target.value })}
                            required
                          />
                        </div>
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="address">Адрес *</Label>
                        <Input
                          id="address"
                          value={form.address}
                          onChange={(e) => setForm({ ...form, address: e.target.value })}
                          placeholder="Улица, дом, квартира"
                          required
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="comment">Комментарий к заказу</Label>
                        <Textarea
                          id="comment"
                          value={form.comment}
                          onChange={(e) => setForm({ ...form, comment: e.target.value })}
                          placeholder="Дополнительная информация для курьера"
                        />
                      </div>
                    </CardContent>
                  </Card>

                  {/* Payment */}
                  <Card className="border-0 shadow-premium">
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <CreditCard className="h-5 w-5" />
                        Оплата
                      </CardTitle>
                      <CardDescription>
                        Выберите способ оплаты
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <RadioGroup value={paymentMethod} onValueChange={setPaymentMethod}>
                        <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                          <RadioGroupItem value="card" id="card" />
                          <Label htmlFor="card" className="flex-1 cursor-pointer">
                            <span className="font-medium">Банковская карта</span>
                          </Label>
                        </div>
                        <div className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                          <RadioGroupItem value="sbp" id="sbp" />
                          <Label htmlFor="sbp" className="flex-1 cursor-pointer">
                            <span className="font-medium">СБП (Система быстрых платежей)</span>
                          </Label>
                        </div>
                      </RadioGroup>
                    </CardContent>
                  </Card>
                </div>

                {/* Summary */}
                <div className="lg:col-span-1">
                  <Card className="border-0 shadow-premium-lg sticky top-24">
                    <CardHeader>
                      <CardTitle>Ваш заказ</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      {cart.map((item) => (
                        <div key={item.product.id} className="flex gap-3">
                          <img
                            src={item.product.image}
                            alt={item.product.name}
                            className="w-12 h-12 object-cover rounded"
                          />
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium line-clamp-1">{item.product.name}</p>
                            <p className="text-sm text-muted-foreground">
                              {item.quantity} × {item.product.price} ₽
                            </p>
                          </div>
                          <p className="font-medium">
                            {(item.product.price * item.quantity).toLocaleString()} ₽
                          </p>
                        </div>
                      ))}
                      
                      <Separator />
                      
                      <div className="space-y-2">
                        <div className="flex justify-between">
                          <span className="text-muted-foreground">Товары</span>
                          <span>{total.toLocaleString()} ₽</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-muted-foreground">Доставка</span>
                          <span className={deliveryCost === 0 ? 'text-green-600' : ''}>
                            {deliveryCost === 0 ? 'Бесплатно' : `${deliveryCost} ₽`}
                          </span>
                        </div>
                        
                        {user && user.bonusBalance > 0 && (
                          <div className="flex items-center gap-2 p-2 bg-muted/50 rounded">
                            <Checkbox
                              id="useBonuses"
                              checked={useBonuses}
                              onCheckedChange={(checked) => setUseBonuses(!!checked)}
                            />
                            <Label htmlFor="useBonuses" className="flex-1 cursor-pointer text-sm">
                              Использовать бонусы ({user.bonusBalance} ₽)
                            </Label>
                          </div>
                        )}
                        
                        {bonusDiscount > 0 && (
                          <div className="flex justify-between text-green-600">
                            <span>Скидка бонусами</span>
                            <span>-{bonusDiscount.toLocaleString()} ₽</span>
                          </div>
                        )}
                      </div>
                      
                      <Separator />
                      
                      <div className="flex justify-between text-lg font-bold">
                        <span>Итого</span>
                        <span>{finalTotal.toLocaleString()} ₽</span>
                      </div>
                      
                      <div className="text-sm text-green-600 bg-green-50 p-2 rounded flex items-center gap-2">
                        <CheckCircle className="h-4 w-4" />
                        + {Math.floor(total * 0.05)} бонусов за заказ
                      </div>
                      
                      <Button 
                        type="submit" 
                        className="w-full" 
                        size="lg"
                        disabled={isLoading}
                      >
                        {isLoading ? 'Оформление...' : 'Подтвердить заказ'}
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </Button>
                    </CardContent>
                  </Card>
                </div>
              </div>
            </form>
          </motion.div>
        </div>
      </main>
      
      <Footer />
    </div>
  );
};

export default Checkout;
