import type { SiteOrder } from '../../../site/api';
import { Badge } from '../../../site/ui';

const orderStatus = {
  cancelled: { label: 'Отменен', variant: 'destructive' },
  delivered: { label: 'Доставлен', variant: 'default' },
  pending: { label: 'Ожидает', variant: 'secondary' },
  processing: { label: 'Обработка', variant: 'default' },
  shipped: { label: 'Доставляется', variant: 'default' },
} as const;

export function OrderBadge({ order }: { order: SiteOrder }) {
  const status = orderStatus[order.status] || orderStatus.pending;

  return <Badge variant={status.variant === 'destructive' ? 'outline' : status.variant}>{status.label}</Badge>;
}

export function isPaid(order: SiteOrder) {
  return order.paymentStatus === 'completed' || order.paidAt !== null;
}
