import { ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { formatDate, formatMoney } from '../../../shared/lib';
import { Badge, Card, EmptyState } from '../../../shared/ui';
import type { Order } from '../../../types';
import { isOrderPaid, orderStatusLabels } from '../../orders/model/orderOptions';

type Props = {
  orders: Order[];
  onSelect: (order: Order) => void;
};

export function RecentOrders({ orders, onSelect }: Props) {
  return (
    <Card className="mt-8 p-6">
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-[#294555]">Последние заказы</h2>
        <Link to="/admin/orders" className="inline-flex items-center gap-2 text-sm font-semibold text-[#294555] hover:text-[#2e8175]">
          Все заказы <ArrowRight className="h-4 w-4" />
        </Link>
      </div>
      {orders.length === 0 ? (
        <EmptyState>Заказы будут отображаться здесь</EmptyState>
      ) : (
        <div className="space-y-3">
          {orders.map((order) => {
            const paid = isOrderPaid(order);
            return (
              <button
                key={order.id}
                type="button"
                className="flex w-full items-center justify-between gap-4 rounded-lg border border-[#dfece9] px-4 py-3 text-left transition hover:bg-[#f5faf8]"
                onClick={() => onSelect(order)}
              >
                <div>
                  <div className="mb-1 flex flex-wrap items-center gap-2">
                    <span className="font-semibold text-[#294555]">{order.id}</span>
                    <Badge tone={order.status === 'processing' ? 'green' : 'gray'}>{orderStatusLabels[order.status] ?? order.status}</Badge>
                    <Badge tone={paid ? 'green' : 'gray'}>{paid ? 'Оплачен' : 'Не оплачен'}</Badge>
                  </div>
                  <p className="text-sm text-[#5f7580]">
                    {order.shipping_address.name || 'Клиент'} • {formatDate(order.created_at)} • {formatMoney(order.total)}
                  </p>
                </div>
                <ArrowRight className="h-4 w-4 text-[#5f7580]" />
              </button>
            );
          })}
        </div>
      )}
    </Card>
  );
}
