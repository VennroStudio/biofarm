import { useState, useEffect } from 'react';
import { Search, Eye, Package } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ordersApi, Order } from '@/data/orders';
import { useToast } from '@/hooks/use-toast';

const statusOptions = [
  { value: 'pending', label: 'Ожидает', variant: 'secondary' as const },
  { value: 'processing', label: 'Обработка', variant: 'default' as const },
  { value: 'shipped', label: 'Отправлен', variant: 'default' as const },
  { value: 'delivered', label: 'Доставлен', variant: 'default' as const },
  { value: 'cancelled', label: 'Отменён', variant: 'destructive' as const },
];

const AdminOrders = () => {
  const { toast } = useToast();
  const [orders, setOrders] = useState<Order[]>([]);
  const [search, setSearch] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [statusFilter, setStatusFilter] = useState<string>('all');

  useEffect(() => {
    // Load all orders (demo: empty initially, would load from API)
    ordersApi.getOrders('all').then(setOrders);
  }, []);

  const filteredOrders = orders.filter(order => {
    const matchesSearch = order.id.toLowerCase().includes(search.toLowerCase()) ||
      order.shippingAddress.name.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const handleStatusChange = async (orderId: string, newStatus: Order['status']) => {
    await ordersApi.updateOrderStatus(orderId, newStatus);
    setOrders(prev => prev.map(o => 
      o.id === orderId ? { ...o, status: newStatus } : o
    ));
    toast({ title: 'Статус обновлён' });
  };

  const getStatusBadge = (status: Order['status']) => {
    const option = statusOptions.find(s => s.value === status);
    return option ? <Badge variant={option.variant}>{option.label}</Badge> : null;
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Заказы</h1>
        <p className="text-muted-foreground">Управление заказами клиентов</p>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input 
                placeholder="Поиск по номеру или имени..." 
                value={search} 
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Фильтр по статусу" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Все статусы</SelectItem>
                {statusOptions.map(s => (
                  <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Badge variant="secondary">{filteredOrders.length} заказов</Badge>
          </div>
        </CardHeader>
        <CardContent>
          {filteredOrders.length === 0 ? (
            <div className="text-center py-12">
              <Package className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
              <p className="text-muted-foreground">Заказы появятся здесь после оформления</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Номер</TableHead>
                    <TableHead>Клиент</TableHead>
                    <TableHead>Дата</TableHead>
                    <TableHead>Сумма</TableHead>
                    <TableHead>Статус</TableHead>
                    <TableHead className="text-right">Действия</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredOrders.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="font-medium">{order.id}</TableCell>
                      <TableCell>
                        <div>
                          <p>{order.shippingAddress.name}</p>
                          <p className="text-sm text-muted-foreground">{order.shippingAddress.phone}</p>
                        </div>
                      </TableCell>
                      <TableCell>
                        {new Date(order.createdAt).toLocaleDateString('ru-RU')}
                      </TableCell>
                      <TableCell className="font-medium">{order.total.toLocaleString()} ₽</TableCell>
                      <TableCell>
                        <Select 
                          value={order.status} 
                          onValueChange={(v) => handleStatusChange(order.id, v as Order['status'])}
                        >
                          <SelectTrigger className="w-[140px]">
                            {getStatusBadge(order.status)}
                          </SelectTrigger>
                          <SelectContent>
                            {statusOptions.map(s => (
                              <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </TableCell>
                      <TableCell className="text-right">
                        <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(order)}>
                          <Eye className="h-4 w-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
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
                  <p>{selectedOrder.shippingAddress.name}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress.phone}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress.email}</p>
                </div>
                <div>
                  <h4 className="font-medium mb-2">Адрес доставки</h4>
                  <p>{selectedOrder.shippingAddress.city}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress.address}</p>
                  <p className="text-muted-foreground">{selectedOrder.shippingAddress.postalCode}</p>
                </div>
              </div>
              
              <div>
                <h4 className="font-medium mb-2">Товары</h4>
                <div className="space-y-2">
                  {selectedOrder.items.map((item) => (
                    <div key={item.productId} className="flex justify-between items-center p-2 bg-muted/50 rounded">
                      <div>
                        <p className="font-medium">{item.productName}</p>
                        <p className="text-sm text-muted-foreground">{item.quantity} × {item.price} ₽</p>
                      </div>
                      <p className="font-medium">{item.price * item.quantity} ₽</p>
                    </div>
                  ))}
                </div>
              </div>
              
              <div className="flex justify-between items-center pt-4 border-t">
                <div>
                  <p className="text-muted-foreground">Способ оплаты: {selectedOrder.paymentMethod}</p>
                  {selectedOrder.bonusUsed > 0 && (
                    <p className="text-muted-foreground">Использовано бонусов: {selectedOrder.bonusUsed} ₽</p>
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

export default AdminOrders;
