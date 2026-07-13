import type { SiteOrder } from '../../../site/api';
import { formatMoney } from '../../../site/format';
import { Button } from '../../../site/ui';

export function OrderDetailsDialog({ order, onClose }: { order: SiteOrder | null; onClose: () => void }) {
  if (!order) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
      <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-background p-6 shadow-premium-lg">
        <div className="mb-6 flex items-center justify-between gap-4">
          <h2 className="text-xl font-bold">Заказ {order.id}</h2>
          <Button size="sm" variant="outline" onClick={onClose}>Закрыть</Button>
        </div>

        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <h4 className="mb-2 font-medium">Клиент</h4>
              <p>{order.shippingAddress.name || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.phone || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.email || 'Не указано'}</p>
            </div>
            <div>
              <h4 className="mb-2 font-medium">Адрес доставки</h4>
              <p>{order.shippingAddress.city || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.address || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.postalCode || 'Не указано'}</p>
            </div>
          </div>

          <div>
            <h4 className="mb-2 font-medium">Товары</h4>
            {order.items.length > 0 ? (
              <div className="space-y-2">
                {order.items.map((item) => (
                  <div className="flex items-center justify-between rounded bg-muted/50 p-2" key={`${item.productId}-${item.productName}`}>
                    <div>
                      <p className="font-medium">{item.productName}</p>
                      <p className="text-sm text-muted-foreground">
                        {item.quantity} x {formatMoney(item.price)}
                      </p>
                    </div>
                    <p className="font-medium">{formatMoney(item.price * item.quantity)}</p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-muted-foreground">Товары не загружены</p>
            )}
          </div>

          <div className="flex items-center justify-between border-t pt-4">
            <div>
              <p className="text-muted-foreground">Способ оплаты: {order.paymentMethod || 'Не указано'}</p>
              {order.bonusUsed > 0 && <p className="text-muted-foreground">Использовано бонусов: {formatMoney(order.bonusUsed)}</p>}
              {order.trackingNumber && <p className="text-muted-foreground">Трекинг: {order.trackingNumber}</p>}
            </div>
            <p className="text-xl font-bold">{formatMoney(order.total)}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
