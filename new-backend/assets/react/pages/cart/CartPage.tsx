import { ArrowRight, Minus, Plus, ShoppingBag, ShoppingCart, Trash2 } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { useEffect, useMemo, useState } from 'react';
import { cartTotal, readCart, removeFromCart, updateQuantity, type CartItem } from '../../site/cart';
import { formatMoney, pluralProduct } from '../../site/format';
import { getStoredUser } from '../../site/api';
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
    <section className="flex min-h-screen items-center justify-center bg-secondary/30 pt-24">
      <div className="px-4 text-center">
        <ShoppingBag className="mx-auto mb-4 h-16 w-16 text-muted-foreground" />
        <h1 className="mb-2 text-2xl font-bold">Корзина пуста</h1>
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
  orderBonusEnabled: boolean;
  orderBonusPercent: number;
};

function CartPage({ orderBonusEnabled, orderBonusPercent }: CartPageProps) {
  const cart = useCartState();
  const user = getStoredUser();
  const total = useMemo(() => cartTotal(cart), [cart]);
  const deliveryCost = total >= 3000 ? 0 : 350;
  const finalTotal = total + deliveryCost;
  const orderBonus = orderBonusEnabled ? Math.floor(total * (orderBonusPercent / 100)) : 0;

  if (cart.length === 0) {
    return <CartEmpty />;
  }

  return (
    <section className="min-h-screen bg-secondary/30 pb-8 pt-24 md:pb-12 md:pt-28">
      <div className="container mx-auto px-4">
        <h1 className="mb-8 flex items-center gap-3 text-2xl font-bold md:text-3xl">
          <ShoppingCart className="h-8 w-8" />
          Корзина
          <span className="text-lg font-normal text-muted-foreground">
            ({cart.length} {pluralProduct(cart.length)})
          </span>
        </h1>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <div className="space-y-4 lg:col-span-2">
            {cart.map((item) => (
              <Card className="border-0 shadow-premium" key={item.product.id}>
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
                            className="h-8 w-8"
                            size="icon"
                            variant="outline"
                            onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
                          >
                            <Minus className="h-3 w-3" />
                          </Button>
                          <span className="w-8 text-center font-medium">{item.quantity}</span>
                          <Button
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
            <Card className="sticky top-24 border-0 shadow-premium-lg">
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
                    До бесплатной доставки: {formatMoney(3000 - total)}
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
                    + {orderBonus} бонусов за заказ
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
    </section>
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
        orderBonusEnabled={root.dataset.orderBonusEnabled === 'true'}
        orderBonusPercent={numberDataset(root.dataset.orderBonusPercent, 5)}
      />
    ));
  });
}
