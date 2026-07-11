import { useState } from 'react';
import { dashboardApi, ordersApi } from '../api/resources';
import { DashboardStatsGrid } from '../features/dashboard/ui/DashboardStatsGrid';
import { QuickActions } from '../features/dashboard/ui/QuickActions';
import { RecentOrders } from '../features/dashboard/ui/RecentOrders';
import { OrderDetailsModal } from '../features/orders/ui/OrderDetailsModal';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { PageHeader } from '../shared/ui';
import type { DashboardStats, Order } from '../types';

export function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useLoadOnMount(async () => {
    const [statsData, orderResult] = await Promise.all([dashboardApi.get(), ordersApi.list()]);
    setStats(statsData);
    setOrders(orderResult.items.slice(0, 5));
  });

  return (
    <>
      <PageHeader title="Дашборд" subtitle="Обзор магазина BioFarm" />
      <DashboardStatsGrid stats={stats} />
      <QuickActions />
      <RecentOrders orders={orders} onSelect={setSelectedOrder} />
      <OrderDetailsModal order={selectedOrder} onClose={() => setSelectedOrder(null)} />
    </>
  );
}
