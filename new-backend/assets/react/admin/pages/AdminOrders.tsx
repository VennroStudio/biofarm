import { Eye, Package, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ordersApi } from '../api/resources';
import {
  AdminTable,
  Badge,
  Button,
  Card,
  EmptyState,
  inputClass,
  Modal,
  PageHeader,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { Order } from '../types';

const money = new Intl.NumberFormat('ru-RU');

const statusOptions = [
  { value: 'pending', label: 'Ожидает' },
  { value: 'processing', label: 'Обработка' },
  { value: 'shipped', label: 'Отправлен' },
  { value: 'delivered', label: 'Доставлен' },
  { value: 'cancelled', label: 'Отменён' },
];

const paymentOptions = [
  { value: 'pending', label: 'Не оплачен' },
  { value: 'completed', label: 'Оплачен' },
  { value: 'failed', label: 'Ошибка оплаты' },
  { value: 'refunded', label: 'Возврат' },
];

function labelByValue(options: Array<{ value: string; label: string }>, value: string) {
  return options.find((option) => option.value === value)?.label ?? value;
}

export function AdminOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  async function load() {
    const result = await ordersApi.list();
    setOrders(result.items);
  }

  useLoadOnMount(load);

  const filteredOrders = useMemo(() => {
    const needle = search.toLowerCase();
    return orders.filter((order) => {
      const matchesSearch =
        order.id.toLowerCase().includes(needle) ||
        String(order.shipping_address.name ?? '').toLowerCase().includes(needle) ||
        String(order.shipping_address.phone ?? '').toLowerCase().includes(needle);
      const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
      return matchesSearch && matchesStatus;
    });
  }, [orders, search, statusFilter]);

  async function changeStatus(order: Order, status: string) {
    await ordersApi.updateStatus(order.id, status);
    await load();
  }

  async function changePayment(order: Order, paymentStatus: string) {
    await ordersApi.updatePaymentStatus(order.id, paymentStatus);
    await load();
  }

  return (
    <>
      <PageHeader title="Заказы" subtitle="Управление заказами клиентов" />

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
            <input
              className={`${inputClass} pl-10`}
              placeholder="Поиск по номеру или имени..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <select className={`${inputClass} !w-44`} value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
            <option value="all">Все статусы</option>
            {statusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
          </select>
          <Badge tone="gray">{filteredOrders.length} заказов</Badge>
        </div>

        {filteredOrders.length === 0 ? (
          <EmptyState>
            <Package className="mx-auto mb-3 h-10 w-10 opacity-50" />
            Заказы появятся здесь после оформления
          </EmptyState>
        ) : (
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
                {filteredOrders.map((order) => {
                  const paid = order.payment_status === 'completed' || order.payment_status === 'paid' || order.paid_at !== null;
                  return (
                    <TableRow key={order.id}>
                      <TableCell className="font-semibold">{order.id}</TableCell>
                      <TableCell>
                        <p>{order.shipping_address.name || 'Клиент'}</p>
                        <p className="text-sm text-[#789083]">{order.shipping_address.phone || 'Телефон не указан'}</p>
                      </TableCell>
                      <TableCell>{new Date(order.created_at).toLocaleDateString('ru-RU')}</TableCell>
                      <TableCell className="font-semibold">{money.format(order.total)} ₽</TableCell>
                      <TableCell>
                        <select className={`${inputClass} h-9 !w-36`} value={order.status} onChange={(event) => void changeStatus(order, event.target.value)}>
                          {statusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
                        </select>
                      </TableCell>
                      <TableCell>
                        <select className={`${inputClass} h-9 !w-36 ${paid ? 'text-[#16a34a]' : ''}`} value={order.payment_status || 'pending'} onChange={(event) => void changePayment(order, event.target.value)}>
                          {paymentOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
                        </select>
                      </TableCell>
                      <TableCell>
                        <div className="flex justify-end">
                          <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(order)} title="Просмотреть">
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
        )}
      </Card>

      <Modal
        open={!!selectedOrder}
        title={`Заказ ${selectedOrder?.id ?? ''}`}
        maxWidth="max-w-2xl"
        onClose={() => setSelectedOrder(null)}
        footer={<Button type="button" variant="outline" onClick={() => setSelectedOrder(null)}>Закрыть</Button>}
      >
        {selectedOrder && (
          <div className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <h4 className="mb-2 font-semibold">Клиент</h4>
                <p>{selectedOrder.shipping_address.name || 'Не указано'}</p>
                <p className="text-[#789083]">{selectedOrder.shipping_address.phone || 'Не указано'}</p>
                <p className="text-[#789083]">{selectedOrder.shipping_address.email || 'Не указано'}</p>
              </div>
              <div>
                <h4 className="mb-2 font-semibold">Заказ</h4>
                <p>Статус: {labelByValue(statusOptions, selectedOrder.status)}</p>
                <p className="text-[#789083]">Оплата: {labelByValue(paymentOptions, selectedOrder.payment_status)}</p>
                <p className="text-[#789083]">Способ оплаты: {selectedOrder.payment_method || 'Не указано'}</p>
              </div>
            </div>
            <div>
              <h4 className="mb-2 font-semibold">Товары</h4>
              <div className="space-y-2">
                {selectedOrder.items.map((item) => (
                  <div key={`${item.product_id}-${item.product_name}`} className="flex justify-between rounded bg-[#f6f5ee] p-3">
                    <span>{item.product_name} × {item.quantity}</span>
                    <span className="font-semibold">{money.format(item.price * item.quantity)} ₽</span>
                  </div>
                ))}
              </div>
            </div>
            <div className="flex justify-between border-t border-[#e4e5da] pt-4">
              <span className="text-[#789083]">Итого</span>
              <span className="text-xl font-bold">{money.format(selectedOrder.total)} ₽</span>
            </div>
          </div>
        )}
      </Modal>
    </>
  );
}
