import { formatMoney } from '../../../shared/lib';
import { Button, Modal } from '../../../shared/ui';
import type { Order } from '../../../types';
import { labelByValue, orderStatusOptions, paymentStatusOptions } from '../model/orderOptions';

type Props = {
  order: Order | null;
  onClose: () => void;
};

export function OrderDetailsModal({ order, onClose }: Props) {
  return (
    <Modal
      open={!!order}
      title={`Заказ ${order?.id ?? ''}`}
      maxWidth="max-w-2xl"
      onClose={onClose}
      footer={<Button type="button" variant="outline" onClick={onClose}>Закрыть</Button>}
    >
      {order && (
        <div className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2">
            <div>
              <h4 className="mb-2 font-semibold">Клиент</h4>
              <p>{order.shipping_address.name || 'Не указано'}</p>
              <p className="text-[#789083]">{order.shipping_address.phone || 'Не указано'}</p>
              <p className="text-[#789083]">{order.shipping_address.email || 'Не указано'}</p>
            </div>
            <div>
              <h4 className="mb-2 font-semibold">Заказ</h4>
              <p>Статус: {labelByValue(orderStatusOptions, order.status)}</p>
              <p className="text-[#789083]">Оплата: {labelByValue(paymentStatusOptions, order.payment_status)}</p>
              <p className="text-[#789083]">Способ оплаты: {order.payment_method || 'Не указано'}</p>
            </div>
          </div>
          <div>
            <h4 className="mb-2 font-semibold">Товары</h4>
            <div className="space-y-2">
              {order.items.map((item) => (
                <div key={`${item.product_id}-${item.product_name}`} className="flex justify-between rounded bg-[#f6f5ee] p-3">
                  <span>{item.product_name} × {item.quantity}</span>
                  <span className="font-semibold">{formatMoney(item.price * item.quantity)}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="flex justify-between border-t border-[#e4e5da] pt-4">
            <span className="text-[#789083]">Итого</span>
            <span className="text-xl font-bold">{formatMoney(order.total)}</span>
          </div>
        </div>
      )}
    </Modal>
  );
}
