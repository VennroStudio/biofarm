import { useMemo, useState } from 'react';
import { ordersApi } from '../api/resources';
import { orderStatusOptions } from '../features/orders/model/orderOptions';
import { OrderDetailsModal } from '../features/orders/ui/OrderDetailsModal';
import { OrdersTable } from '../features/orders/ui/OrdersTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Badge, Card, inputClass, PageHeader, SearchField } from '../shared/ui';
import type { Order } from '../types';

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
          <SearchField placeholder="Поиск по номеру или имени..." value={search} onChange={setSearch} />
          <select className={`${inputClass} !w-44`} value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
            <option value="all">Все статусы</option>
            {orderStatusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
          </select>
          <Badge tone="gray">{filteredOrders.length} заказов</Badge>
        </div>

        <OrdersTable
          orders={filteredOrders}
          onChangePayment={(order, paymentStatus) => void changePayment(order, paymentStatus)}
          onChangeStatus={(order, status) => void changeStatus(order, status)}
          onSelect={setSelectedOrder}
        />
      </Card>

      <OrderDetailsModal order={selectedOrder} onClose={() => setSelectedOrder(null)} />
    </>
  );
}
