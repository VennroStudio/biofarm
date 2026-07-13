import { ChevronRight, Package } from 'lucide-react';
import type { SiteOrder } from '../../../site/api';
import { formatDate, formatMoney } from '../../../site/format';
import { Badge, Card, CardContent, CardDescription, CardHeader, CardTitle, LinkButton } from '../../../site/ui';
import { isPaid, OrderBadge } from './orderDisplay';

type Props = {
  orders: SiteOrder[];
  onSelectOrder: (order: SiteOrder) => void;
};

export function OrdersPanel({ orders, onSelectOrder }: Props) {
  return (
    <Card className="border-0 shadow-premium">
      <CardHeader>
        <CardTitle>История заказов</CardTitle>
        <CardDescription>Все ваши заказы</CardDescription>
      </CardHeader>
      <CardContent>
        {orders.length === 0 ? (
          <div className="py-12 text-center">
            <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
            <p className="mb-4 text-muted-foreground">У вас пока нет заказов</p>
            <LinkButton href="/catalog">Перейти в каталог</LinkButton>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.map((order) => (
              <button
                className="flex w-full cursor-pointer items-center justify-between rounded-lg border p-4 text-left transition-colors hover:bg-muted/50"
                key={order.id}
                type="button"
                onClick={() => onSelectOrder(order)}
              >
                <div className="flex-1">
                  <div className="mb-1 flex flex-wrap items-center gap-3">
                    <span className="font-medium">{order.id}</span>
                    <OrderBadge order={order} />
                    <Badge className={isPaid(order) ? 'bg-green-500 hover:bg-green-600' : ''} variant={isPaid(order) ? 'default' : 'outline'}>
                      {isPaid(order) ? 'Оплачен' : 'Не оплачен'}
                    </Badge>
                  </div>
                  <p className="text-sm text-muted-foreground">
                    {formatDate(order.createdAt)} • {formatMoney(order.total)}
                  </p>
                  {order.trackingNumber && <p className="text-sm text-muted-foreground">Трекинг: {order.trackingNumber}</p>}
                </div>
                <ChevronRight className="h-5 w-5 text-muted-foreground" />
              </button>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
