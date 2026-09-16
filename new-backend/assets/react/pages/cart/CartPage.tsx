import type { Dashboard } from '../../program/shared';
import { OfferImport } from './OfferImport';
import { ArrowRight, Minus, Plus, ShoppingBag, ShoppingCart, Trash2 } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { useEffect, useMemo, useState } from 'react';
import { cartTotal, readCart, removeFromCart, updateQuantity, type CartItem } from '../../site/cart';
import { formatMoney, pluralProduct } from '../../site/format';
import { getStoredUser, getToken, request } from '../../site/api';
import { Button, Card, CardContent, CardFooter, CardHeader, CardTitle, LinkButton, Separator } from '../../site/ui';

function useCartState() {
  const [cart, setCart] = useState<CartItem[]>(() => readCart());

  useEffect(() => {
    const refresh = () => setCart(readCart());
    window.addEventListener('biofarm-cart-updated', refresh);
    window.addEventListener('cartUpdated', refresh);

    return () => {
      window.removeEventListener('biofarm-cart-updated', refresh);
      window.removeEventListener('cartUpdated', refresh);
    };
  }, []);

  return cart;
}

function numberDataset(value: string | undefined, fallback: number) {
  const numberValue = Number(value);

  return Number.isFinite(numberValue) ? numberValue : fallback;
}

function CartEmpty() {
  return (
    <section className="flex min-h-[60vh] items-center justify-center bg-secondary/30 px-4 pb-12 pt-[128px]">
      <div className="px-4 text-center">
        <ShoppingBag className="mx-auto mb-4 h-16 w-16 text-muted-foreground" />
        <h1 className="mb-2 text-3xl font-normal tracking-tight text-primary">Корзина пуста</h1>
        <p className="mb-6 text-muted-foreground">Добавьте товары из каталога</p>
        <LinkButton href="/catalog" size="lg">
          Перейти в каталог
          <ArrowRight className="h-4 w-4" />
        </LinkButton>
      </div>
    </section>
  );
}

type CartPageProps = {
  cdekDeliveryPrice: number;
  freeDeliveryThreshold: number;
  orderBonusEnabled: boolean;
  orderBonusPercent: number;
};

function CartPage({ cdekDeliveryPrice, freeDeliveryThreshold, orderBonusEnabled }: CartPageProps) {
  const [program,setProgram] = useState<Dashboard|null>(null);
  useEffect(() => { if(getToken()) void request<Dashboard>('/v1/program').then(setProgram).catch(()=>undefined); }, []);
  const cart = useCartState();
  const user = getStoredUser();
  const total = useMemo(() => cartTotal(cart), [cart]);
  const deliveryCost = total >= freeDeliveryThreshold ? 0 : cdekDeliveryPrice;
  const finalTotal = total + deliveryCost;
  const orderBonus = orderBonusEnabled && program ? Math.floor(total * program.rates.buyerBps / 100) / 100 : 0;

  if (cart.length === 0) {
    return <><OfferImport /><CartEmpty /></>;
  }

  return (
    <><OfferImport /><section className="bg-secondary/30 pb-10 pt-[120px] md:pb-12 md:pt-[128px]">
      <div className="container mx-auto px-4 sm:px-6">
        <h1 className="mb-6 flex flex-wrap items-center gap-3 text-3xl font-normal tracking-tight text-primary md:text-4xl">
          <ShoppingCart className="h-8 w-8" />
          Корзина
          <span className="text-lg font-normal text-muted-foreground">
            ({cart.length} {pluralProduct(cart.length)})
          </span>
        </h1>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-4 lg:col-span-2">
            {cart.map((item) => (
              <Card key={item.product.id}>
                <CardContent className="p-4">
                  <div className="flex gap-4">
                    <a href={`/product/${item.product.slug}`}>
                      <img
                        alt={item.product.name}
                        className="h-24 w-24 rounded-lg object-cover"
                        src={item.product.image}
                      />
                    </a>

                    <div className="min-w-0 flex-1">
                      <a className="line-clamp-2 font-medium transition-colors hover:text-primary" href={`/product/${item.product.slug}`}>
                        {item.product.name}
                      </a>
                      <p className="mt-1 text-sm text-muted-foreground">{item.product.weight}</p>

                      <div className="mt-3 flex items-center justify-between gap-3 max-sm:flex-col max-sm:items-start">
                        <div className="flex items-center gap-2">
                          <Button
                            aria-label={`Уменьшить количество: ${item.product.name}`}
                            className="h-8 w-8"
                            size="icon"
                            variant="outline"
                            onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
                          >
                            <Minus className="h-3 w-3" />
                          </Button>
                          <span className="w-8 text-center font-medium">{item.quantity}</span>
                          <Button
                            aria-label={`Увеличить количество: ${item.product.name}`}
                            className="h-8 w-8"
                            size="icon"
                            variant="outline"
                            onClick={() => updateQuantity(item.product.id, item.quantity + 1)}
                          >
                            <Plus className="h-3 w-3" />
                          </Button>
                        </div>

                        <div className="flex items-center gap-4">
                          <div className="text-right">
                            <p className="font-bold">{formatMoney(item.product.price * item.quantity)}</p>
                            {item.quantity > 1 && (
                              <p className="text-sm text-muted-foreground">
                                {formatMoney(item.product.price)} x {item.quantity}
                              </p>
                            )}
                          </div>
                          <Button
                            aria-label={`Удалить из корзины: ${item.product.name}`}
                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            size="icon"
                            variant="ghost"
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
            ))}
          </div>

          <div>
            <Card className="sticky top-24">
              <CardHeader>
                <CardTitle>Итого</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Товары ({cart.length})</span>
                  <span>{formatMoney(total)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Доставка</span>
                  <span className={deliveryCost === 0 ? 'text-green-600' : ''}>
                    {deliveryCost === 0 ? 'Бесплатно' : formatMoney(deliveryCost)}
                  </span>
                </div>
                {deliveryCost > 0 && (
                  <p className="rounded bg-muted/50 p-2 text-sm text-muted-foreground">
                    До бесплатной доставки: {formatMoney(Math.max(0, freeDeliveryThreshold - total))}
                  </p>
                )}
                {user && user.bonusBalance > 0 && (
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Доступно бонусов</span>
                    <span className="font-medium text-primary">{formatMoney(user.bonusBalance)}</span>
                  </div>
                )}
                <Separator />
                <div className="flex justify-between text-lg font-bold">
                  <span>К оплате</span>
                  <span>{formatMoney(finalTotal)}</span>
                </div>
                {orderBonus > 0 && (
                  <div className="rounded bg-green-50 p-2 text-sm text-green-600">
                    До {orderBonus.toFixed(2)} бонусов; итог после скидок и исключений
                  </div>
                )}
              </CardContent>
              <CardFooter className="flex-col gap-3">
                <LinkButton className="w-full" href="/checkout" size="lg">
                  Оформить заказ
                  <ArrowRight className="h-4 w-4" />
                </LinkButton>
                <LinkButton className="w-full" href="/catalog" variant="outline">
                  Продолжить покупки
                </LinkButton>
              </CardFooter>
            </Card>
          </div>
        </div>
      </div>
    </section></>
  );
}

export function mountCartPage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="cart-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') {
      return;
    }
    root.dataset.mounted = 'true';
    createRoot(root).render((
      <CartPage
        cdekDeliveryPrice={numberDataset(root.dataset.cdekDeliveryPrice, 350)}
        freeDeliveryThreshold={numberDataset(root.dataset.freeDeliveryThreshold, 3000)}
        orderBonusEnabled={root.dataset.orderBonusEnabled === 'true'}
        orderBonusPercent={numberDataset(root.dataset.orderBonusPercent, 5)}
      />
    ));
  });
}
