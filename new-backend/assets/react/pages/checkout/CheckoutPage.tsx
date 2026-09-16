import { CreditCard, Mail, MapPin, Phone, Truck, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { cartTotal, clearCart, readCart, type CartItem } from '../../site/cart';
import {
  clearAuth,
  createOrder,
  getStoredUser,
  getToken,
  getUserAddresses,
  refreshUser,
  type ShippingAddress,
  type SiteUser,
  type UserAddress,
} from '../../site/api';
import { formatMoney } from '../../site/format';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label, Textarea } from '../../site/ui';
import { CheckoutSummary } from './components/CheckoutSummary';
import { RadioOption } from './components/RadioOption';

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

type CheckoutPageProps = {
  cdekDeliveryPrice: number;
  freeDeliveryThreshold: number;
  orderBonusEnabled: boolean;
  orderBonusPercent: number;
  orderBonusSpendLimitPercent: number;
  postDeliveryPrice: number;
  promoCodesEnabled: boolean;
};

function CheckoutPage({
  cdekDeliveryPrice,
  freeDeliveryThreshold,
  orderBonusEnabled,
  orderBonusPercent,
  orderBonusSpendLimitPercent,
  postDeliveryPrice,
  promoCodesEnabled,
}: CheckoutPageProps) {
  const [cart] = useState<CartItem[]>(() => readCart());
  const [user, setUser] = useState<SiteUser | null>(() => (getToken() ? getStoredUser() : null));
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [useBonuses, setUseBonuses] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [deliveryMethod, setDeliveryMethod] = useState('cdek');
  const [promoCode, setPromoCode] = useState('');
  const [form, setForm] = useState<ShippingAddress>(() => emptyAddress(getToken() ? getStoredUser() : null));
  const [addresses, setAddresses] = useState<UserAddress[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState('');

  const total = useMemo(() => cartTotal(cart), [cart]);
  const deliveryCostFor = (baseCost: number) => (total >= freeDeliveryThreshold ? 0 : baseCost);
  const cdekDeliveryCost = deliveryCostFor(cdekDeliveryPrice);
  const postDeliveryCost = deliveryCostFor(postDeliveryPrice);
  const deliveryCost = deliveryMethod === 'post' ? postDeliveryCost : cdekDeliveryCost;
  const maxBonusSpend = Math.floor((total + deliveryCost) * (orderBonusSpendLimitPercent / 100));
  const bonusDiscount = useBonuses && user && orderBonusEnabled ? Math.min(user.bonusBalance, maxBonusSpend) : 0;
  const finalTotal = total + deliveryCost - bonusDiscount;
  const orderBonus = user && orderBonusEnabled ? Math.floor(total * (orderBonusPercent / 100)) : 0;

  useEffect(() => {
    if (cart.length === 0) {
      window.location.href = '/cart';
      return;
    }

    if (!getToken()) {
      return;
    }

    void Promise.all([refreshUser(), getUserAddresses()]).then(([updatedUser, loadedAddresses]) => {
      if (updatedUser) {
        setUser(updatedUser);
        setAddresses(loadedAddresses);
        setForm((current) => ({
          ...current,
          name: current.name || updatedUser.name,
          phone: current.phone || updatedUser.phone || '',
          email: current.email || updatedUser.email,
        }));

        const defaultAddress = loadedAddresses.find((address) => address.isDefault) || loadedAddresses[0];
        if (defaultAddress) {
          setSelectedAddressId(String(defaultAddress.id));
          setForm({
            name: defaultAddress.name || updatedUser.name,
            phone: defaultAddress.phone || updatedUser.phone || '',
            email: defaultAddress.email || updatedUser.email,
            city: defaultAddress.city,
            address: defaultAddress.address,
            postalCode: defaultAddress.postalCode,
            comment: defaultAddress.comment || '',
          });
        }
      }
    }).catch(() => {
      clearAuth();
      setUser(null);
      setUseBonuses(false);
    });
  }, [cart.length]);

  function applySavedAddress(addressId: string) {
    setSelectedAddressId(addressId);
    const savedAddress = addresses.find((address) => String(address.id) === addressId);
    if (!savedAddress) {
      return;
    }

    setForm({
      name: savedAddress.name || user?.name || '',
      phone: savedAddress.phone || user?.phone || '',
      email: savedAddress.email || user?.email || '',
      city: savedAddress.city,
      address: savedAddress.address,
      postalCode: savedAddress.postalCode,
      comment: savedAddress.comment || '',
    });
  }

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
        paymentMethod,
        deliveryMethod,
        useBonuses,
        promoCodesEnabled ? promoCode : undefined,
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
    <section className="bg-secondary/30 pb-10 pt-[120px] md:pb-12 md:pt-[128px]">
      <div className="container mx-auto px-4 sm:px-6">
        <h1 className="mb-6 text-3xl font-normal tracking-tight text-primary md:text-4xl">Оформление заказа</h1>

        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              <Card>
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

              <Card>
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
                    <span className="font-medium">{cdekDeliveryCost === 0 ? 'Бесплатно' : formatMoney(cdekDeliveryCost)}</span>
                  </RadioOption>
                  <RadioOption checked={deliveryMethod === 'post'} name="delivery" value="post" onChange={setDeliveryMethod}>
                    <span className="flex-1">
                      <span className="font-medium">Почта России</span>
                      <span className="ml-2 text-muted-foreground">от 5 дней</span>
                    </span>
                    <span className="font-medium">{postDeliveryCost === 0 ? 'Бесплатно' : formatMoney(postDeliveryCost)}</span>
                  </RadioOption>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <MapPin className="h-5 w-5" />
                    Адрес доставки
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {user && addresses.length > 0 && (
                    <div className="space-y-2">
                      <Label htmlFor="checkout-saved-address">Сохраненный адрес</Label>
                      <select
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/30"
                        id="checkout-saved-address"
                        value={selectedAddressId}
                        onChange={(event) => applySavedAddress(event.target.value)}
                      >
                        {addresses.map((address) => (
                          <option key={address.id} value={address.id}>
                            {address.label}: {address.city}, {address.address}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}
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

              <Card>
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

              {promoCodesEnabled && (
                <Card>
                  <CardHeader>
                    <CardTitle>Промокод</CardTitle>
                    <CardDescription>Скидка будет рассчитана после подтверждения заказа</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Input
                      autoComplete="off"
                      aria-label="Промокод"
                      placeholder="Введите промокод"
                      value={promoCode}
                      onChange={(event) => setPromoCode(event.target.value)}
                    />
                  </CardContent>
                </Card>
              )}
            </div>

            <div>
              <CheckoutSummary
                bonusDiscount={bonusDiscount}
                cart={cart}
                deliveryCost={deliveryCost}
                error={error}
                finalTotal={finalTotal}
                isLoading={isLoading}
                orderBonus={orderBonus}
                orderBonusEnabled={orderBonusEnabled}
                setUseBonuses={setUseBonuses}
                total={total}
                useBonuses={useBonuses}
                user={user}
              />
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
        cdekDeliveryPrice={numberDataset(root.dataset.cdekDeliveryPrice, 350)}
        freeDeliveryThreshold={numberDataset(root.dataset.freeDeliveryThreshold, 3000)}
        orderBonusEnabled={root.dataset.orderBonusEnabled === 'true'}
        orderBonusPercent={numberDataset(root.dataset.orderBonusPercent, 5)}
        orderBonusSpendLimitPercent={numberDataset(root.dataset.orderBonusSpendLimitPercent, 30)}
        postDeliveryPrice={numberDataset(root.dataset.postDeliveryPrice, 250)}
        promoCodesEnabled={root.dataset.promoCodesEnabled === 'true'}
      />
    ));
  });
}
