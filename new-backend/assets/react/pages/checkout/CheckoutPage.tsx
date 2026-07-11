import { ArrowRight, CheckCircle, CreditCard, Mail, MapPin, Phone, Truck, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, type ReactNode, useEffect, useMemo, useState } from 'react';
import { cartTotal, clearCart, readCart, type CartItem } from '../../site/cart';
import { createOrder, getStoredUser, getToken, refreshUser, type ShippingAddress, type SiteUser } from '../../site/api';
import { formatMoney } from '../../site/format';
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label, Separator, Textarea } from '../../site/ui';

function emptyAddress(user: SiteUser | null): ShippingAddress {
  return {
    name: user?.name || '',
    phone: user?.phone || '',
    email: user?.email || '',
    city: '',
    address: '',
    postalCode: '',
    comment: '',
  };
}

function numberDataset(value: string | undefined, fallback: number) {
  const numberValue = Number(value);

  return Number.isFinite(numberValue) ? numberValue : fallback;
}

function RadioOption({
  children,
  checked,
  name,
  onChange,
  value,
}: {
  children: ReactNode;
  checked: boolean;
  name: string;
  onChange: (value: string) => void;
  value: string;
}) {
  return (
    <label className="flex cursor-pointer items-center space-x-3 rounded-lg border p-3 transition-colors hover:bg-muted/50">
      <input
        checked={checked}
        className="h-4 w-4 accent-primary"
        name={name}
        type="radio"
        value={value}
        onChange={() => onChange(value)}
      />
      {children}
    </label>
  );
}

type CheckoutPageProps = {
  orderBonusEnabled: boolean;
  orderBonusPercent: number;
};

function CheckoutPage({ orderBonusEnabled, orderBonusPercent }: CheckoutPageProps) {
  const [cart] = useState<CartItem[]>(() => readCart());
  const [user, setUser] = useState<SiteUser | null>(() => getStoredUser());
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [useBonuses, setUseBonuses] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [deliveryMethod, setDeliveryMethod] = useState('cdek');
  const [form, setForm] = useState<ShippingAddress>(() => emptyAddress(getStoredUser()));

  const total = useMemo(() => cartTotal(cart), [cart]);
  const deliveryCost = total >= 3000 ? 0 : 350;
  const bonusDiscount = useBonuses ? Math.min(user?.bonusBalance || 0, total * 0.3) : 0;
  const finalTotal = total + deliveryCost - bonusDiscount;
  const orderBonus = orderBonusEnabled ? Math.floor(total * (orderBonusPercent / 100)) : 0;

  useEffect(() => {
    if (cart.length === 0) {
      window.location.href = '/cart';
      return;
    }

    if (!getToken()) {
      window.location.href = '/login?redirect=/checkout';
      return;
    }

    void refreshUser().then((updatedUser) => {
      if (updatedUser) {
        setUser(updatedUser);
        setForm((current) => ({
          ...current,
          name: current.name || updatedUser.name,
          phone: current.phone || updatedUser.phone || '',
          email: current.email || updatedUser.email,
        }));
      }
    }).catch(() => {
      window.location.href = '/login?redirect=/checkout';
    });
  }, [cart.length]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');

    if (cart.length === 0) {
      window.location.href = '/cart';
      return;
    }

    setIsLoading(true);
    try {
      const order = await createOrder(
        cart,
        form,
        paymentMethod === 'card' ? 'Банковская карта' : 'СБП',
        bonusDiscount,
        finalTotal,
      );
      clearCart();
      window.location.href = `/order-success?order=${encodeURIComponent(order?.id || '')}`;
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Попробуйте еще раз');
    } finally {
      setIsLoading(false);
    }
  }

  if (cart.length === 0) {
    return null;
  }

  return (
    <section className="min-h-screen bg-secondary/30 pb-8 pt-24 md:pb-12 md:pt-28">
      <div className="container mx-auto px-4">
        <h1 className="mb-8 text-2xl font-bold md:text-3xl">Оформление заказа</h1>

        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <User className="h-5 w-5" />
                    Контактные данные
                  </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="checkout-name">Имя *</Label>
                      <Input
                        id="checkout-name"
                        required
                        value={form.name}
                        onChange={(event) => setForm({ ...form, name: event.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="checkout-phone">Телефон *</Label>
                      <div className="relative">
                        <Phone className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                          className="pl-10"
                          id="checkout-phone"
                          placeholder="+7 (999) 123-45-67"
                          required
                          type="tel"
                          value={form.phone}
                          onChange={(event) => setForm({ ...form, phone: event.target.value })}
                        />
                      </div>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="checkout-email">Email *</Label>
                    <div className="relative">
                      <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                      <Input
                        className="pl-10"
                        id="checkout-email"
                        required
                        type="email"
                        value={form.email}
                        onChange={(event) => setForm({ ...form, email: event.target.value })}
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Truck className="h-5 w-5" />
                    Доставка
                  </CardTitle>
                  <CardDescription>Выберите способ доставки</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <RadioOption checked={deliveryMethod === 'cdek'} name="delivery" value="cdek" onChange={setDeliveryMethod}>
                    <span className="flex-1">
                      <span className="font-medium">СДЭК</span>
                      <span className="ml-2 text-muted-foreground">от 2 дней</span>
                    </span>
                    <span className="font-medium">{deliveryCost === 0 ? 'Бесплатно' : formatMoney(deliveryCost)}</span>
                  </RadioOption>
                  <RadioOption checked={deliveryMethod === 'post'} name="delivery" value="post" onChange={setDeliveryMethod}>
                    <span className="flex-1">
                      <span className="font-medium">Почта России</span>
                      <span className="ml-2 text-muted-foreground">от 5 дней</span>
                    </span>
                    <span className="font-medium">250 ₽</span>
                  </RadioOption>
                </CardContent>
              </Card>

              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <MapPin className="h-5 w-5" />
                    Адрес доставки
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="checkout-city">Город *</Label>
                      <Input
                        id="checkout-city"
                        required
                        value={form.city}
                        onChange={(event) => setForm({ ...form, city: event.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="checkout-postal">Индекс *</Label>
                      <Input
                        id="checkout-postal"
                        required
                        value={form.postalCode}
                        onChange={(event) => setForm({ ...form, postalCode: event.target.value })}
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="checkout-address">Адрес *</Label>
                    <Input
                      id="checkout-address"
                      placeholder="Улица, дом, квартира"
                      required
                      value={form.address}
                      onChange={(event) => setForm({ ...form, address: event.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="checkout-comment">Комментарий к заказу</Label>
                    <Textarea
                      id="checkout-comment"
                      placeholder="Дополнительная информация для курьера"
                      value={form.comment}
                      onChange={(event) => setForm({ ...form, comment: event.target.value })}
                    />
                  </div>
                </CardContent>
              </Card>

              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <CreditCard className="h-5 w-5" />
                    Оплата
                  </CardTitle>
                  <CardDescription>Выберите способ оплаты</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <RadioOption checked={paymentMethod === 'card'} name="payment" value="card" onChange={setPaymentMethod}>
                    <span className="font-medium">Банковская карта</span>
                  </RadioOption>
                  <RadioOption checked={paymentMethod === 'sbp'} name="payment" value="sbp" onChange={setPaymentMethod}>
                    <span className="font-medium">СБП (Система быстрых платежей)</span>
                  </RadioOption>
                </CardContent>
              </Card>
            </div>

            <div>
              <Card className="sticky top-24 border-0 shadow-premium-lg">
                <CardHeader>
                  <CardTitle>Ваш заказ</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {cart.map((item) => (
                    <div className="flex gap-3" key={item.product.id}>
                      <img alt={item.product.name} className="h-12 w-12 rounded object-cover" src={item.product.image} />
                      <div className="min-w-0 flex-1">
                        <p className="line-clamp-1 text-sm font-medium">{item.product.name}</p>
                        <p className="text-sm text-muted-foreground">
                          {item.quantity} x {formatMoney(item.product.price)}
                        </p>
                      </div>
                      <p className="font-medium">{formatMoney(item.product.price * item.quantity)}</p>
                    </div>
                  ))}

                  <Separator />

                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Товары</span>
                      <span>{formatMoney(total)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Доставка</span>
                      <span className={deliveryCost === 0 ? 'text-green-600' : ''}>
                        {deliveryCost === 0 ? 'Бесплатно' : formatMoney(deliveryCost)}
                      </span>
                    </div>

                    {user && user.bonusBalance > 0 && (
                      <label className="flex cursor-pointer items-center gap-2 rounded bg-muted/50 p-2">
                        <input
                          checked={useBonuses}
                          className="h-4 w-4 accent-primary"
                          type="checkbox"
                          onChange={(event) => setUseBonuses(event.target.checked)}
                        />
                        <span className="flex-1 text-sm">Использовать бонусы ({formatMoney(user.bonusBalance)})</span>
                      </label>
                    )}

                    {bonusDiscount > 0 && (
                      <div className="flex justify-between text-green-600">
                        <span>Скидка бонусами</span>
                        <span>-{formatMoney(bonusDiscount)}</span>
                      </div>
                    )}
                  </div>

                  <Separator />

                  <div className="flex justify-between text-lg font-bold">
                    <span>Итого</span>
                    <span>{formatMoney(finalTotal)}</span>
                  </div>

                  {orderBonus > 0 && (
                    <div className="flex items-center gap-2 rounded bg-green-50 p-2 text-sm text-green-600">
                      <CheckCircle className="h-4 w-4" />
                      + {orderBonus} бонусов за заказ
                    </div>
                  )}

                  {error && <p className="rounded bg-destructive/10 p-2 text-sm text-destructive">{error}</p>}

                  <Button className="w-full" disabled={isLoading} size="lg" type="submit">
                    {isLoading ? 'Оформление...' : 'Подтвердить заказ'}
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                </CardContent>
              </Card>
            </div>
          </div>
        </form>
      </div>
    </section>
  );
}

export function mountCheckoutPage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="checkout-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') {
      return;
    }
    root.dataset.mounted = 'true';
    createRoot(root).render((
      <CheckoutPage
        orderBonusEnabled={root.dataset.orderBonusEnabled === 'true'}
        orderBonusPercent={numberDataset(root.dataset.orderBonusPercent, 5)}
      />
    ));
  });
}
