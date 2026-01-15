import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  ShoppingCart, Users, TrendingUp, 
  Package, FileText, ArrowRight, DollarSign, Wallet
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { adminApi, DashboardStats } from '@/data/admin';
import { ordersApi, Order } from '@/data/orders';

const AdminDashboard = () => {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentOrders, setRecentOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useEffect(() => {
    adminApi.getDashboardStats().then(setStats);
    ordersApi.getAllOrders()
      .then(orders => {
        const sorted = orders.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
        setRecentOrders(sorted.slice(0, 5));
      })
      .catch(console.error);
  }, []);

  const statCards = [
    { 
      title: 'Выручка', 
      value: stats ? `${stats.totalRevenue.toLocaleString()} ₽` : '—', 
      icon: DollarSign, 
      color: 'bg-green-100 text-green-600',
      change: 'Всего оплачено'
    },
    { 
      title: 'Заказов всего', 
      value: stats?.totalOrders ?? '—', 
      icon: ShoppingCart, 
      color: 'bg-blue-100 text-blue-600',
      change: 'Всего создано'
    },
    { 
      title: 'Пользователей', 
      value: stats?.totalUsers ?? '—', 
      icon: Users, 
      color: 'bg-purple-100 text-purple-600',
      change: 'Зарегистрировано'
    },
    { 
      title: 'Заявок на вывод', 
      value: stats?.pendingWithdrawals ?? '—', 
      icon: Wallet, 
      color: 'bg-amber-100 text-amber-600',
      change: stats?.totalWithdrawalAmount ? `${stats.totalWithdrawalAmount.toLocaleString()} ₽` : 'Нет заявок'
    },
  ];

  const quickLinks = [
    { href: '/admin/products', label: 'Добавить товар', icon: Package },
    { href: '/admin/orders', label: 'Просмотреть заказы', icon: ShoppingCart },
    { href: '/admin/blog', label: 'Написать статью', icon: FileText },
    { href: '/admin/withdrawals', label: 'Заявки на вывод', icon: Wallet },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold">Дашборд</h1>
        <p className="text-muted-foreground">Обзор магазина BioFarm</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((card, index) => {
          const Icon = card.icon;
          return (
            <motion.div
              key={card.title}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
            >
              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-muted-foreground">{card.title}</p>
                      <p className="text-2xl font-bold mt-1">{card.value}</p>
                      <p className="text-xs text-muted-foreground mt-1">{card.change}</p>
                    </div>
                    <div className={`p-3 rounded-full ${card.color}`}>
                      <Icon className="h-6 w-6" />
                    </div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          );
        })}
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Быстрые действия</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {quickLinks.map((link) => {
              const Icon = link.icon;
              return (
                <Button 
                  key={link.href} 
                  variant="outline" 
                  className="h-auto py-4 flex-col gap-2"
                  asChild
                >
                  <Link to={link.href}>
                    <Icon className="h-6 w-6" />
                    <span>{link.label}</span>
                  </Link>
                </Button>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Recent Orders */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Последние заказы</CardTitle>
          <Button variant="ghost" size="sm" asChild>
            <Link to="/admin/orders">
              Все заказы <ArrowRight className="ml-2 h-4 w-4" />
            </Link>
          </Button>
        </CardHeader>
        <CardContent>
          {recentOrders.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <TrendingUp className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>Заказы будут отображаться здесь</p>
            </div>
          ) : (
            <div className="space-y-3">
              {recentOrders.map((order) => {
                const statusMap = {
                  pending: { label: 'Ожидает', variant: 'secondary' as const },
                  processing: { label: 'Обработка', variant: 'default' as const },
                  shipped: { label: 'Отправлен', variant: 'default' as const },
                  delivered: { label: 'Доставлен', variant: 'default' as const },
                  cancelled: { label: 'Отменён', variant: 'destructive' as const },
                };
                const status = statusMap[order.status] || statusMap.pending;
                const isPaid = order.paymentStatus === 'completed' || order.paidAt !== null;
                return (
                  <div 
                    key={order.id}
                    className="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
                    onClick={() => setSelectedOrder(order)}
                  >
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-1">
                        <span className="font-medium">{order.id}</span>
                        <Badge variant={status.variant}>{status.label}</Badge>
                        <Badge variant={isPaid ? 'default' : 'outline'} className={isPaid ? 'bg-green-500 hover:bg-green-600' : ''}>
                          {isPaid ? 'Оплачен' : 'Не оплачен'}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">
                        {order.shippingAddress?.name || 'Клиент'} • {new Date(order.createdAt).toLocaleDateString('ru-RU')} • {order.total.toLocaleString()} ₽
                      </p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-muted-foreground" />
                  </div>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Order Details Dialog */}
      <Dialog open={!!selectedOrder} onOpenChange={() => setSelectedOrder(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Заказ {selectedOrder?.id}</DialogTitle>
          </DialogHeader>
          {selectedOrder && (
            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <h4 className="font-medium mb-2">Клиент</h4>
                  <p>{selectedOrder.shippingAddress?.name || 'Не указано'}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress?.phone || 'Не указано'}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress?.email || 'Не указано'}</p>
                </div>
                <div>
                  <h4 className="font-medium mb-2">Адрес доставки</h4>
                  <p>{selectedOrder.shippingAddress?.city || 'Не указано'}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress?.address || 'Не указано'}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress?.postalCode || 'Не указано'}</p>
                </div>
              </div>
              
              <div>
                <h4 className="font-medium mb-2">Товары</h4>
                {selectedOrder.items && selectedOrder.items.length > 0 ? (
                  <div className="space-y-2">
                    {selectedOrder.items.map((item, index) => (
                      <div key={`${item.productId}-${index}`} className="flex justify-between items-center p-2 bg-muted/50 rounded">
                        <div>
                          <p className="font-medium">{item.productName}</p>
                          <p className="text-sm text-muted-foreground">{item.quantity} × {item.price.toLocaleString()} ₽</p>
                        </div>
                        <p className="font-medium">{(item.price * item.quantity).toLocaleString()} ₽</p>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-muted-foreground">Товары не загружены</p>
                )}
              </div>
              
              <div className="flex justify-between items-center pt-4 border-t">
                <div>
                  <p className="text-muted-foreground">Способ оплаты: {selectedOrder.paymentMethod || 'Не указано'}</p>
                  {selectedOrder.bonusUsed > 0 && (
                    <p className="text-muted-foreground">Использовано бонусов: {selectedOrder.bonusUsed.toLocaleString()} ₽</p>
                  )}
                </div>
                <p className="text-xl font-bold">{selectedOrder.total.toLocaleString()} ₽</p>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminDashboard;
