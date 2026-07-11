import { Eye, Package } from 'lucide-react';
import { formatDate, formatMoney } from '../../../shared/lib';
import {
  AdminTable,
  Button,
  EmptyState,
  inputClass,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../../../shared/ui';
import type { Order } from '../../../types';
import { isOrderPaid, orderStatusOptions, paymentStatusOptions } from '../model/orderOptions';

type Props = {
  orders: Order[];
  onChangePayment: (order: Order, paymentStatus: string) => void;
  onChangeStatus: (order: Order, status: string) => void;
  onSelect: (order: Order) => void;
};

export function OrdersTable({ orders, onChangePayment, onChangeStatus, onSelect }: Props) {
  if (orders.length === 0) {
    return (
      <EmptyState>
        <Package className="mx-auto mb-3 h-10 w-10 opacity-50" />
        Заказы появятся здесь после оформления
      </EmptyState>
    );
  }

  return (
    <div className="overflow-x-auto">
      <AdminTable>
        <TableHead>
          <tr>
            <TableHeaderCell>Номер</TableHeaderCell>
            <TableHeaderCell>Клиент</TableHeaderCell>
            <TableHeaderCell>Дата</TableHeaderCell>
            <TableHeaderCell>Сумма</TableHeaderCell>
            <TableHeaderCell>Статус</TableHeaderCell>
            <TableHeaderCell>Оплата</TableHeaderCell>
            <TableHeaderCell className="text-right">Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {orders.map((order) => {
            const paid = isOrderPaid(order);
            return (
              <TableRow key={order.id}>
                <TableCell className="font-semibold">{order.id}</TableCell>
                <TableCell>
                  <p>{order.shipping_address.name || 'Клиент'}</p>
                  <p className="text-sm text-[#789083]">{order.shipping_address.phone || 'Телефон не указан'}</p>
                </TableCell>
                <TableCell>{formatDate(order.created_at)}</TableCell>
                <TableCell className="font-semibold">{formatMoney(order.total)}</TableCell>
                <TableCell>
                  <select className={`${inputClass} h-9 !w-36`} value={order.status} onChange={(event) => onChangeStatus(order, event.target.value)}>
                    {orderStatusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
                  </select>
                </TableCell>
                <TableCell>
                  <select className={`${inputClass} h-9 !w-36 ${paid ? 'text-[#16a34a]' : ''}`} value={order.payment_status || 'pending'} onChange={(event) => onChangePayment(order, event.target.value)}>
                    {paymentStatusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
                  </select>
                </TableCell>
                <TableCell>
                  <div className="flex justify-end">
                    <Button variant="ghost" size="icon" onClick={() => onSelect(order)} title="Просмотреть">
                      <Eye className="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            );
          })}
        </tbody>
      </AdminTable>
    </div>
  );
}
