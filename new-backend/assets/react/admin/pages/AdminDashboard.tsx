import { ArrowRight, DollarSign, FileText, Package, ShoppingCart, Users, Wallet } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { dashboardApi, ordersApi } from '../api/resources';
import { Badge, Button, Card, EmptyState, Modal, PageHeader } from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { DashboardStats, Order } from '../types';

const money = new Intl.NumberFormat('ru-RU');

const statusLabels: Record<string, string> = {
  pending: 'Ожидает',
  processing: 'Обработка',
  shipped: 'Отправлен',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

export function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useLoadOnMount(async () => {
    const [statsData, orderResult] = await Promise.all([dashboardApi.get(), ordersApi.list()]);
    setStats(statsData);
    setOrders(orderResult.items.slice(0, 5));
  });

  const statCards = [
    {
      title: 'Выручка',
      value: `${money.format(stats?.total_revenue ?? 0)} ₽`,
      caption: 'Всего оплачено',
      icon: DollarSign,
      color: 'bg-[#c9ffd8] text-[#16a34a]',
    },
    {
      title: 'Заказов всего',
      value: stats?.total_orders ?? 0,
      caption: 'Всего создано',
      icon: ShoppingCart,
      color: 'bg-[#dbeafe] text-[#2563eb]',
    },
    {
      title: 'Пользователей',
      value: stats?.total_users ?? 0,
      caption: 'Зарегистрировано',
      icon: Users,
      color: 'bg-[#f3d9ff] text-[#a855f7]',
    },
    {
      title: 'Заявок на вывод',
      value: stats?.pending_withdrawals ?? 0,
      caption: stats?.total_withdrawal_amount ? `${money.format(stats.total_withdrawal_amount)} ₽` : 'Нет заявок',
      icon: Wallet,
      color: 'bg-[#fff2bf] text-[#f59e0b]',
    },
  ];

  const quickLinks = [
    { href: '/admin/products', label: 'Добавить товар', icon: Package },
    { href: '/admin/orders', label: 'Просмотреть заказы', icon: ShoppingCart },
    { href: '/admin/blog', label: 'Написать статью', icon: FileText },
    { href: '/admin/withdrawals', label: 'Заявки на вывод', icon: Wallet },
  ];

  return (
    <>
      <PageHeader title="Дашборд" subtitle="Обзор магазина BioFarm" />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {statCards.map((card) => {
          const Icon = card.icon;
          return (
            <Card key={card.title} className="p-6">
              <div className="flex items-center justify-between gap-4">
                <div>
                  <p className="text-sm text-[#789083]">{card.title}</p>
                  <p className="mt-1 text-2xl font-bold text-[#26382d]">{card.value}</p>
                  <p className="mt-1 text-xs text-[#789083]">{card.caption}</p>
                </div>
                <span className={`grid h-12 w-12 place-items-center rounded-full ${card.color}`}>
                  <Icon className="h-6 w-6" />
                </span>
              </div>
            </Card>
          );
        })}
      </div>

      <Card className="mt-8 p-6">
        <h2 className="mb-5 text-2xl font-bold text-[#26382d]">Быстрые действия</h2>
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {quickLinks.map((link) => {
            const Icon = link.icon;
            return (
              <Link
                key={link.href}
                to={link.href}
                className="flex min-h-20 flex-col items-center justify-center gap-2 rounded-md border border-[#e4e5da] bg-[#fbfaf4] px-4 py-4 text-sm font-semibold text-[#26382d] transition hover:bg-white"
              >
                <Icon className="h-5 w-5" />
                {link.label}
              </Link>
            );
          })}
        </div>
      </Card>

      <Card className="mt-8 p-6">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-2xl font-bold text-[#26382d]">Последние заказы</h2>
          <Link to="/admin/orders" className="inline-flex items-center gap-2 text-sm font-semibold text-[#26382d] hover:text-[#2f7d4b]">
            Все заказы <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
        {orders.length === 0 ? (
          <EmptyState>Заказы будут отображаться здесь</EmptyState>
        ) : (
          <div className="space-y-3">
            {orders.map((order) => {
              const isPaid = order.payment_status === 'completed' || order.payment_status === 'paid' || order.paid_at !== null;
              return (
                <button
                  key={order.id}
                  type="button"
                  className="flex w-full items-center justify-between gap-4 rounded-lg border border-[#e4e5da] px-4 py-3 text-left transition hover:bg-[#fbfaf4]"
                  onClick={() => setSelectedOrder(order)}
                >
                  <div>
                    <div className="mb-1 flex flex-wrap items-center gap-2">
                      <span className="font-semibold text-[#26382d]">{order.id}</span>
                      <Badge tone={order.status === 'processing' ? 'green' : 'gray'}>{statusLabels[order.status] ?? order.status}</Badge>
                      <Badge tone={isPaid ? 'green' : 'gray'}>{isPaid ? 'Оплачен' : 'Не оплачен'}</Badge>
                    </div>
                    <p className="text-sm text-[#789083]">
                      {order.shipping_address.name || 'Клиент'} • {new Date(order.created_at).toLocaleDateString('ru-RU')} • {money.format(order.total)} ₽
                    </p>
                  </div>
                  <ArrowRight className="h-4 w-4 text-[#789083]" />
                </button>
              );
            })}
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
                <h4 className="mb-2 font-semibold">Адрес доставки</h4>
                <p>{selectedOrder.shipping_address.city || 'Не указано'}</p>
                <p className="text-[#789083]">{selectedOrder.shipping_address.address || 'Не указано'}</p>
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
