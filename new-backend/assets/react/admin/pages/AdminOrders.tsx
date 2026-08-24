import { useMemo, useState } from 'react';
import { ordersApi } from '../api/resources';
import { orderStatusOptions } from '../features/orders/model/orderOptions';
import { OrderDetailsModal } from '../features/orders/ui/OrderDetailsModal';
import { OrdersTable } from '../features/orders/ui/OrdersTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Card, ErrorAlert, inputClass, PageHeader, SearchField } from '../shared/ui';
import type { Order } from '../types';

export function AdminOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [detailsError, setDetailsError] = useState<string | null>(null);
  const [savingDetails, setSavingDetails] = useState(false);

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
    setError(null);
    try {
      await ordersApi.updateStatus(order.id, status);
      await load();
    } catch (statusError) {
      setError(messageFromError(statusError, 'Не удалось изменить статус заказа'));
    }
  }

  async function changePayment(order: Order, paymentStatus: string) {
    setError(null);
    try {
      await ordersApi.updatePaymentStatus(order.id, paymentStatus);
      await load();
    } catch (paymentError) {
      setError(messageFromError(paymentError, 'Не удалось изменить статус оплаты'));
    }
  }

  async function saveOrder(order: Order, payload: Record<string, unknown>) {
    setDetailsError(null);
    setSavingDetails(true);
    try {
      await ordersApi.update(order.id, payload);
      setSelectedOrder(null);
      await load();
    } catch (saveError) {
      setDetailsError(messageFromError(saveError, 'Не удалось сохранить заказ'));
    } finally {
      setSavingDetails(false);
    }
  }

  function openDetails(order: Order) {
    setDetailsError(null);
    setSelectedOrder(order);
  }

  return (
    <>
      <PageHeader title="Заказы" subtitle="Управление заказами клиентов" />

      <Card className="p-6">
        <ErrorAlert className="mb-5">{error}</ErrorAlert>

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
          onSelect={openDetails}
        />
      </Card>

      <OrderDetailsModal
        key={selectedOrder?.id ?? 'empty'}
        order={selectedOrder}
        error={detailsError}
        saving={savingDetails}
        onClose={() => {
          setSelectedOrder(null);
          setDetailsError(null);
        }}
        onSave={saveOrder}
      />
    </>
  );
}
