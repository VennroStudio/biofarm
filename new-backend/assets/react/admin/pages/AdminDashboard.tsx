import { useState } from 'react';
import { dashboardApi, ordersApi } from '../api/resources';
import { DashboardStatsGrid } from '../features/dashboard/ui/DashboardStatsGrid';
import { QuickActions } from '../features/dashboard/ui/QuickActions';
import { RecentOrders } from '../features/dashboard/ui/RecentOrders';
import { OrderDetailsModal } from '../features/orders/ui/OrderDetailsModal';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { PageHeader } from '../shared/ui';
import type { DashboardStats, Order } from '../types';

export function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [orderError, setOrderError] = useState<string | null>(null);
  const [savingOrder, setSavingOrder] = useState(false);

  async function load() {
    const [statsData, orderResult] = await Promise.all([dashboardApi.get(), ordersApi.list()]);
    setStats(statsData);
    setOrders(orderResult.items.slice(0, 5));
  }

  useLoadOnMount(load);

  async function saveOrder(order: Order, payload: Record<string, unknown>) {
    setOrderError(null);
    setSavingOrder(true);
    try {
      await ordersApi.update(order.id, payload);
      setSelectedOrder(null);
      await load();
    } catch (saveError) {
      setOrderError(messageFromError(saveError, 'Не удалось сохранить заказ'));
    } finally {
      setSavingOrder(false);
    }
  }

  function openOrder(order: Order) {
    setOrderError(null);
    setSelectedOrder(order);
  }

  return (
    <>
      <PageHeader title="Дашборд" subtitle="Обзор магазина БИОФАРМ" />
      <DashboardStatsGrid stats={stats} />
      <QuickActions />
      <RecentOrders orders={orders} onSelect={openOrder} />
      <OrderDetailsModal
        key={selectedOrder?.id ?? 'empty'}
        order={selectedOrder}
        error={orderError}
        saving={savingOrder}
        onClose={() => {
          setSelectedOrder(null);
          setOrderError(null);
        }}
        onPaymentRefresh={async () => {
          try { await load(); } catch (refreshError) {
            setOrderError(messageFromError(refreshError, 'Не удалось обновить заказы'));
          }
        }}
        onSave={saveOrder}
      />
    </>
  );
}
