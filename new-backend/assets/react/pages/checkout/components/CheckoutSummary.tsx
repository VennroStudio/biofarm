import { ArrowRight, CheckCircle } from 'lucide-react';
import type { CartItem } from '../../../site/cart';
import type { SiteUser } from '../../../site/api';
import { formatMoney } from '../../../site/format';
import { Button, Card, CardContent, CardHeader, CardTitle, Separator } from '../../../site/ui';

type Props = {
  bonusDiscount: number;
  cart: CartItem[];
  deliveryCost: number;
  error: string;
  finalTotal: number;
  isLoading: boolean;
  orderBonus: number;
  setUseBonuses: (value: boolean) => void;
  total: number;
  useBonuses: boolean;
  user: SiteUser | null;
};

export function CheckoutSummary({
  bonusDiscount,
  cart,
  deliveryCost,
  error,
  finalTotal,
  isLoading,
  orderBonus,
  setUseBonuses,
  total,
  useBonuses,
  user,
}: Props) {
  return (
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
  );
}
