import type { Order } from '../../../types';

export const orderStatusOptions = [
  { value: 'pending', label: 'Ожидает' },
  { value: 'processing', label: 'Обработка' },
  { value: 'shipped', label: 'Отправлен' },
  { value: 'delivered', label: 'Доставлен' },
  { value: 'cancelled', label: 'Отменён' },
];

export const paymentStatusOptions = [
  { value: 'pending', label: 'Не оплачен' },
  { value: 'completed', label: 'Оплачен' },
  { value: 'failed', label: 'Ошибка оплаты' },
  { value: 'refunded', label: 'Возврат' },
];

export const orderStatusLabels = Object.fromEntries(orderStatusOptions.map((option) => [option.value, option.label]));

export function labelByValue(options: Array<{ value: string; label: string }>, value: string) {
  return options.find((option) => option.value === value)?.label ?? value;
}

export function isOrderPaid(order: Order) {
  return order.payment_status === 'completed' || order.payment_status === 'paid' || order.paid_at !== null;
}
