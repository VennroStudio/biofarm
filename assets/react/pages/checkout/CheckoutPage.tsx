import { ConsentFields, consentVersion, formConsents, offerVersion } from '../../shared/ConsentFields';
import { OfferImport } from '../cart/OfferImport';
import type { Dashboard } from '../../program/shared';
import { startPayment } from '../../site/payment';
import { CreditCard, Mail, MapPin, Phone, Truck, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { cartTotal, readCart, type CartItem } from '../../site/cart';
import {
  createOrder,
  completeCheckoutPhone,
  request,
  getStoredUser,
  getToken,
  getUserAddresses,
  refreshUser,
  SiteRequestError,
  type ShippingAddress,
  type SiteUser,
  type UserAddress,
} from '../../site/api';
import { formatMoney } from '../../site/format';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label, Textarea } from '../../site/ui';
import { CheckoutSummary } from './components/CheckoutSummary';
import { OzonPickupPicker, type OzonPickupPoint } from './components/OzonPickupPicker';
import { RadioOption } from './components/RadioOption';
import { normalizePhone, PhoneVerification } from '../../shared/PhoneVerification';

const livePickup = 'ozon-pickup';
const ozonApi = '/v1/ozon-delivery';
type OzonMethod = typeof livePickup;
type OzonQuote = { fingerprint: string; dates: string[]; shipments: { schema: string; from: string; to: string; costMinor: number }[]; deliveryCost: number };
type OzonQuoteInput = {
  method: OzonMethod;
  pickupPointId: number;
  phone: string;
  items: Array<{ productId: number; quantity: number }>;
};

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
  ozonMethod: OzonMethod | null;
  phoneVerificationEnabled: boolean;
  smsAvailable: boolean;
  postDeliveryPrice: number;
  promoCodesEnabled: boolean;
};

function CheckoutPage({
  cdekDeliveryPrice,
  freeDeliveryThreshold,
  orderBonusEnabled,
  orderBonusSpendLimitPercent,
  ozonMethod,
  phoneVerificationEnabled,
  smsAvailable,
  postDeliveryPrice,
  promoCodesEnabled,
}: CheckoutPageProps) {
  const [program, setProgram] = useState<Dashboard | null>(null);
  const [cart] = useState<CartItem[]>(() => readCart());
  const [user, setUser] = useState<SiteUser | null>(() => (getToken() ? getStoredUser() : null));
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [useBonuses, setUseBonuses] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('card');
  const [deliveryMethod, setDeliveryMethod] = useState('cdek');
  const [pickupPoint, setPickupPoint] = useState<OzonPickupPoint | null>(null);
  const [quote, setQuote] = useState<{ input: OzonQuoteInput; attempt: number; result?: OzonQuote; error?: string } | null>(null);
  const [quoteAttempt, setQuoteAttempt] = useState(0);
  const [personalDataChecked, setPersonalDataChecked] = useState(false);
  const [promoCode, setPromoCode] = useState('');
  const [form, setForm] = useState<ShippingAddress>(() => emptyAddress(getToken() ? getStoredUser() : null));
  const [addresses, setAddresses] = useState<UserAddress[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState('');
  const [rememberAddress, setRememberAddress] = useState(true);
  const formRef = useRef<HTMLFormElement>(null);

  const total = useMemo(() => cartTotal(cart), [cart]);
  const deliveryCostFor = (baseCost: number) => (total >= freeDeliveryThreshold ? 0 : baseCost);
  const cdekDeliveryCost = deliveryCostFor(cdekDeliveryPrice);
  const postDeliveryCost = deliveryCostFor(postDeliveryPrice);
  const pickupId = pickupPoint?.id;
  const ozonEnabled = ozonMethod !== null;
  const normalAddresses = addresses.filter(address => !address.ozonDeliveryMethod);
  const savedPickupPoints = addresses.filter(address => address.ozonDeliveryMethod === ozonMethod && address.ozonDestinationId !== undefined);
  const savedPickupPoint = savedPickupPoints.find(address => address.ozonDestinationId === pickupId);
  const phone = normalizePhone(form.phone);
  const verified = Boolean(getToken() && user?.phoneVerifiedAt && phone && normalizePhone(user.phone ?? '') === phone);
  const hasConsent = user?.personalDataConsentValid === true;
  const canUsePersonalData = hasConsent || personalDataChecked;
  const newPersonalDataConsent = !hasConsent && personalDataChecked;
  const quoteInput = useMemo<OzonQuoteInput | null>(() => ozonMethod && pickupId !== undefined && cart.length > 0 && phone && verified && canUsePersonalData ? {
    method: ozonMethod,
    pickupPointId: pickupId,
    phone,
    items: cart.map(({ product, quantity }) => ({ productId: product.id, quantity })),
  } : null, [canUsePersonalData, cart, ozonMethod, phone, pickupId, verified]);
  const matchingQuote = quote?.input === quoteInput && quote.attempt === quoteAttempt ? quote : null;
  const currentQuote = matchingQuote?.result;
  const formatDeliveryDate = (date: string) => new Date(date).toLocaleDateString('ru-RU', { timeZone: 'Europe/Moscow' });
  const deliveryCost = ozonEnabled ? currentQuote?.deliveryCost ?? null : deliveryMethod === 'post' ? postDeliveryCost : cdekDeliveryCost;
  const maxBonusSpend = Math.floor(total * (orderBonusSpendLimitPercent / 100));
  const bonusDiscount = useBonuses && user && orderBonusEnabled ? Math.min(user.bonusBalance, maxBonusSpend) : 0;
  const finalTotal = deliveryCost === null ? null : total + deliveryCost - bonusDiscount;
  const orderBonus = user && orderBonusEnabled && program && !promoCode ? Math.floor(Math.max(0, total - bonusDiscount) * program.rates.buyerBps / 100) / 100 : 0;

  useEffect(() => {
    if (cart.length === 0) {
      window.location.href = '/cart';
      return;
    }

    if (!getToken()) {
      return;
    }

    void request<Dashboard>('/v1/program').then(setProgram).catch(() => undefined);
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

        const standardAddresses = loadedAddresses.filter(address => !address.ozonDeliveryMethod);
        const defaultAddress = standardAddresses.find((address) => address.isDefault) || standardAddresses[0];
        if (defaultAddress && !ozonEnabled) {
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
      if (!getToken()) {
        setUser(null);
        setUseBonuses(false);
      }
      setError('Не удалось загрузить данные аккаунта. Обновите страницу, чтобы повторить попытку.');
    });
  }, [cart.length, ozonEnabled]);

  useEffect(() => {
    if (!quoteInput) return;
    const controller = new AbortController();
    const timer = window.setTimeout(() => {
      void request<OzonQuote>(`${ozonApi}/quote`, {
        method: 'POST',
        body: { ...quoteInput, personalDataConsent: newPersonalDataConsent, consentVersion },
        signal: controller.signal,
      }).then(result => {
        if (!controller.signal.aborted) setQuote({ input: quoteInput, attempt: quoteAttempt, result });
      }).catch(cause => {
        if (!controller.signal.aborted) {
          const message = cause instanceof SiteRequestError ? cause.message : 'Не удалось проверить доставку Ozon.';
          setQuote({ input: quoteInput, attempt: quoteAttempt, error: message });
          if (cause instanceof SiteRequestError && cause.code === 1) {
            void refreshUser().then(updated => {
              if (!controller.signal.aborted) setUser(updated);
            }).catch(() => undefined);
          }
        }
      });
    }, 200);
    return () => {
      window.clearTimeout(timer);
      controller.abort();
    };
  }, [newPersonalDataConsent, quoteInput, quoteAttempt]);

  function applySavedAddress(addressId: string) {
    setSelectedAddressId(addressId);
    const savedAddress = addresses.find((address) => String(address.id) === addressId);
    if (!savedAddress) {
      setForm(current => ({ ...current, city: '', address: '', postalCode: '', comment: '' }));
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

  function selectPickupPoint(point: OzonPickupPoint | null) {
    setPickupPoint(point);
    setError('');
  }

  function changeAddressField(field: 'city' | 'address' | 'postalCode' | 'comment', value: string) {
    setSelectedAddressId('');
    setForm(current => ({ ...current, [field]: value }));
  }

  function getConsents() {
    return formConsents(formRef.current!);
  }

  async function confirmPhone(challenge: string) {
    const account = await completeCheckoutPhone(challenge, { name: form.name, email: form.email, consents: getConsents() });
    if (!account) throw new Error('Не удалось загрузить аккаунт. Войдите ещё раз.');
    setUser(account);
    if (account.personalDataConsentValid) setPersonalDataChecked(false);
    setForm(current => ({ ...current, name: current.name || account.name, email: current.email || account.email }));
    void request<Dashboard>('/v1/program').then(setProgram).catch(() => undefined);
    void getUserAddresses().then(setAddresses).catch(() => undefined);
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');

    if (cart.length === 0) {
      window.location.href = '/cart';
      return;
    }

    const saved = sessionStorage.getItem('biofarm_checkout_order');
    if (saved) { window.location.href = `/order-success?order=${encodeURIComponent(saved)}`; return; }
    if ((phoneVerificationEnabled && !verified) || (ozonEnabled && !currentQuote)) return;
    setIsLoading(true);
    try {
      const order = await createOrder(
        cart,
        form,
        paymentMethod,
        ozonMethod ?? deliveryMethod,
        useBonuses,
        promoCodesEnabled ? promoCode : undefined,
        { ...formConsents(event.currentTarget), offerAccepted: true, offerVersion },
        {
          ozonQuote: currentQuote?.fingerprint,
          ozonPickupPointId: pickupId,
          rememberAddress: Boolean(user && rememberAddress),
        },
      );
      if (!order) throw new Error('Не удалось получить номер заказа');
      sessionStorage.setItem('biofarm_checkout_order', order.id);
      sessionStorage.setItem(`biofarm_order_cart_${order.id}`, JSON.stringify(cart));
      if (order.paymentStatus !== 'completed') {
        try { const state = await startPayment(order.id); if (state.confirmationUrl) return; } catch { /* Saved order can retry payment on its own page. */ }
      }
      window.location.href = `/order-success?order=${encodeURIComponent(order?.id || '')}`;
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Попробуйте еще раз');
      if (user && submitError instanceof SiteRequestError && submitError.code === 1) {
        void refreshUser().then(setUser).catch(() => undefined);
      }
      if (ozonEnabled && submitError instanceof SiteRequestError && submitError.code === 43) {
        setQuoteAttempt(attempt => attempt + 1);
      }
    } finally {
      setIsLoading(false);
    }
  }

  if (cart.length === 0) {
    return null;
  }

  const rememberAddressControl = <label className="flex items-center gap-2 text-sm">
    <input type="checkbox" checked={rememberAddress} disabled={isLoading} className="h-4 w-4 accent-primary"
      onChange={event => setRememberAddress(event.target.checked)} />
    Сохранить адрес в личном кабинете
  </label>;

  return (
    <section className="bg-secondary/30 pb-10 pt-[120px] md:pb-12 md:pt-[128px]">
      <div className="container mx-auto px-4 sm:px-6">
        <p className="mb-3 text-sm">Бонусы рассчитываются от оплаченных товаров после скидок и списания бонусов, без доставки. </p>
        <h1 className="mb-6 text-3xl font-normal tracking-tight text-primary md:text-4xl">Оформление заказа</h1>

        <form ref={formRef} onSubmit={handleSubmit}>
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
                      <Label htmlFor="checkout-name">{ozonMethod === livePickup || (phoneVerificationEnabled && !user) ? 'Имя и фамилия *' : 'Имя *'}</Label>
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
                  {!hasConsent && <ConsentFields onChange={setPersonalDataChecked} />}
                  {phoneVerificationEnabled && <>
                    <PhoneVerification key={phone ?? 'empty'} phone={phone} verified={verified} available={smsAvailable} showAccountNotice={!user} endpoint="/v1/checkout/phone"
                      getConsents={getConsents} onConfirm={confirmPhone} disabled={isLoading || !canUsePersonalData} />
                  </>}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Truck className="h-5 w-5" />
                    Доставка
                  </CardTitle>
                  <CardDescription>{ozonEnabled ? 'Пункт выдачи Ozon' : 'Выберите способ доставки'}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {ozonEnabled ? <>
                    {savedPickupPoints.length > 0 && (
                      <div className="space-y-2">
                        <Label htmlFor="checkout-saved-ozon-point">Сохранённый пункт выдачи</Label>
                        <select id="checkout-saved-ozon-point" disabled={isLoading}
                          className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                          value={savedPickupPoint?.id ?? ''}
                          onChange={event => {
                            const saved = savedPickupPoints.find(address => address.id === Number(event.target.value));
                            selectPickupPoint(saved?.ozonDestinationId ? { id: saved.ozonDestinationId, address: saved.address } : null);
                          }}>
                          <option value="">Выбрать другой пункт выдачи</option>
                          {savedPickupPoints.map(address => <option key={address.id} value={address.id}>{address.address}</option>)}
                        </select>
                      </div>
                    )}
                    <OzonPickupPicker selected={pickupPoint} onSelect={selectPickupPoint} disabled={isLoading} endpoint={`${ozonApi}/pickup-points`} />
                    {user && pickupPoint && !savedPickupPoint && rememberAddressControl}
                    {pickupPoint && !verified && <p className="text-sm text-muted-foreground">Подтвердите телефон для расчёта доставки.</p>}
                    {quoteInput && !matchingQuote && <p role="status" className="text-sm text-muted-foreground">Уточняем стоимость и срок доставки…</p>}
                    {matchingQuote?.error && <div className="space-y-2">
                      <p role="alert" className="text-sm text-destructive">{matchingQuote.error}</p>
                      <button type="button" className="text-sm font-medium text-primary underline" onClick={() => setQuoteAttempt(attempt => attempt + 1)}>Повторить проверку</button>
                    </div>}
                    {currentQuote && (
                      <div role="status" className="space-y-1 text-sm text-primary">
                        {currentQuote.shipments.map((shipment, index) => {
                          const from = formatDeliveryDate(shipment.from);
                          const to = formatDeliveryDate(shipment.to);
                          return <p key={index}>Отправление {index + 1} · {shipment.schema === 'FBO' ? 'со склада Ozon' : 'со склада продавца'} · {from}{from !== to && ` – ${to}`} · {shipment.costMinor / 100} ₽</p>;
                        })}
                      </div>
                    )}
                  </> : <>
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
                  </>}
                </CardContent>
              </Card>

              {!ozonEnabled && <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <MapPin className="h-5 w-5" />
                    Адрес доставки
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {user && normalAddresses.length > 0 && (
                    <div className="space-y-2">
                      <Label htmlFor="checkout-saved-address">Сохранённый адрес</Label>
                      <select
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/30"
                        id="checkout-saved-address"
                        value={selectedAddressId}
                        onChange={(event) => applySavedAddress(event.target.value)}
                      >
                        <option value="">Ввести новый адрес</option>
                        {normalAddresses.map((address) => (
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
                        onChange={(event) => changeAddressField('city', event.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="checkout-postal">Индекс *</Label>
                      <Input
                        id="checkout-postal"
                        required
                        value={form.postalCode}
                        onChange={(event) => changeAddressField('postalCode', event.target.value)}
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
                      onChange={(event) => changeAddressField('address', event.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="checkout-comment">Комментарий к заказу</Label>
                    <Textarea
                      id="checkout-comment"
                      placeholder="Дополнительная информация к доставке"
                      value={form.comment}
                      onChange={(event) => setForm(current => ({ ...current, comment: event.target.value }))}
                    />
                  </div>
                  {user && !selectedAddressId && rememberAddressControl}
                </CardContent>
              </Card>}

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

            <div className="space-y-4">
              <p className="text-sm text-muted-foreground"><a className="underline" href="/dostavka">Доставка и оплата</a> · <a className="underline" href="/vozvrat">Возврат и обмен</a> · <a className="underline" href="/bonus-rules">Правила бонусной программы</a></p>
              <CheckoutSummary
                bonusDiscount={bonusDiscount}
                canSubmit={canUsePersonalData && (!phoneVerificationEnabled || verified) && (!ozonEnabled || Boolean(currentQuote))}
                cart={cart}
                deliveryCost={deliveryCost}
                error={error}
                finalTotal={finalTotal}
                isLoading={isLoading}
                orderBonus={orderBonus}
                orderBonusEnabled={orderBonusEnabled}
                ozonDelivery={ozonEnabled}
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
      <OfferImport>
        <CheckoutPage
          cdekDeliveryPrice={numberDataset(root.dataset.cdekDeliveryPrice, 350)}
          freeDeliveryThreshold={numberDataset(root.dataset.freeDeliveryThreshold, 3000)}
          orderBonusEnabled={root.dataset.orderBonusEnabled === 'true'}
          orderBonusPercent={numberDataset(root.dataset.orderBonusPercent, 3)}
          orderBonusSpendLimitPercent={numberDataset(root.dataset.orderBonusSpendLimitPercent, 100)}
          ozonMethod={root.dataset.ozonMethod === livePickup ? livePickup : null}
          phoneVerificationEnabled={root.dataset.phoneVerificationEnabled === 'true'}
          smsAvailable={root.dataset.smsAvailable === 'true'}
          postDeliveryPrice={numberDataset(root.dataset.postDeliveryPrice, 250)}
          promoCodesEnabled={root.dataset.promoCodesEnabled === 'true'}
        />
      </OfferImport>
    ));
  });
}
