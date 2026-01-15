import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  User, Package, Gift, Settings, LogOut, 
  Copy, Check, Users, Wallet, ChevronRight,
  Edit, Phone, Mail
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { useAuthContext } from '@/contexts/AuthContext';
import { authApi, ReferralInfo } from '@/data/users';
import { ordersApi, Order } from '@/data/orders';
import { useToast } from '@/hooks/use-toast';

const Profile = () => {
  const navigate = useNavigate();
  const { user, logout, updateProfile, refreshUser, isAuthenticated, isLoading } = useAuthContext();
  const { toast } = useToast();
  
  const [orders, setOrders] = useState<Order[]>([]);
  const [referralOrders, setReferralOrders] = useState<Order[]>([]);
  const [referralInfo, setReferralInfo] = useState<ReferralInfo | null>(null);
  const [copied, setCopied] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState('');
  const [editPhone, setEditPhone] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useEffect(() => {
    // Ждем завершения загрузки перед проверкой аутентификации
    if (isLoading) {
      return;
    }
    
    if (!isAuthenticated) {
      navigate('/login');
      return;
    }
    
    if (user) {
      // Обновляем данные пользователя с сервера для получения актуального баланса
      refreshUser().then((updatedUser) => {
        if (updatedUser) {
          setEditName(updatedUser.name);
          setEditPhone(updatedUser.phone || '');
          
          ordersApi.getOrders(updatedUser.id).then(setOrders);
          // Загружаем информацию о рефералах только для партнеров
          if (updatedUser.isPartner) {
            authApi.getReferralInfo(updatedUser.id).then(setReferralInfo);
            ordersApi.getReferralOrders(updatedUser.id).then(setReferralOrders);
          }
        }
      });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated, isLoading, user?.id]); // Используем user?.id для отслеживания изменений пользователя


  const handleLogout = async () => {
    await logout();
    navigate('/');
    toast({ title: 'Вы вышли из аккаунта' });
  };

  const copyReferralLink = () => {
    const link = `${window.location.origin}?ref=${user?.id}`;
    navigator.clipboard.writeText(link);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
    toast({ title: 'Ссылка скопирована!' });
  };

  const handleSaveProfile = async () => {
    await updateProfile({ name: editName, phone: editPhone });
    setIsEditing(false);
    toast({ title: 'Профиль обновлён' });
  };

  const getStatusBadge = (status: Order['status']) => {
    const statusMap = {
      pending: { label: 'Ожидает', variant: 'secondary' as const },
      processing: { label: 'Обработка', variant: 'default' as const },
      shipped: { label: 'Доставляется', variant: 'default' as const },
      delivered: { label: 'Доставлен', variant: 'default' as const },
      cancelled: { label: 'Отменён', variant: 'destructive' as const },
    };
    return statusMap[status];
  };

  // Показываем загрузку пока проверяем аутентификацию
  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-muted-foreground">Загрузка...</p>
      </div>
    );
  }

  if (!user) return null;

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      
      <main className="flex-1 pt-24 pb-8 md:pt-28 md:pb-12 bg-secondary/30">
        <div className="container px-4">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
              <div>
                <h1 className="text-2xl md:text-3xl font-bold">Личный кабинет</h1>
                <p className="text-muted-foreground">Добро пожаловать, {user.name}!</p>
              </div>
              <Button variant="outline" onClick={handleLogout}>
                <LogOut className="h-4 w-4 mr-2" />
                Выйти
              </Button>
            </div>

            {/* Stats cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
              <Card className="border-0 shadow-premium">
                <CardContent className="flex items-center gap-4 p-6">
                  <div className="p-3 rounded-full bg-primary/10">
                    <Wallet className="h-6 w-6 text-primary" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{user.bonusBalance} ₽</p>
                    <p className="text-sm text-muted-foreground">Бонусный баланс</p>
                  </div>
                </CardContent>
              </Card>
              
              <Card className="border-0 shadow-premium">
                <CardContent className="flex items-center gap-4 p-6">
                  <div className="p-3 rounded-full bg-accent/10">
                    <Package className="h-6 w-6 text-accent" />
                  </div>
                  <div>
                    <p className="text-2xl font-bold">{orders.length}</p>
                    <p className="text-sm text-muted-foreground">Заказов</p>
                  </div>
                </CardContent>
              </Card>
              
              {user.isPartner && (
                <Card className="border-0 shadow-premium">
                  <CardContent className="flex items-center gap-4 p-6">
                    <div className="p-3 rounded-full bg-green-100">
                      <Users className="h-6 w-6 text-green-600" />
                    </div>
                    <div>
                      <p className="text-2xl font-bold">{referralInfo?.referredUsers || 0}</p>
                      <p className="text-sm text-muted-foreground">Приглашённых</p>
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>

            {/* Main content */}
            <Tabs defaultValue="profile" className="space-y-6">
              <TabsList className="bg-card border shadow-sm">
                <TabsTrigger value="profile" className="gap-2">
                  <User className="h-4 w-4" />
                  <span className="hidden sm:inline">Профиль</span>
                </TabsTrigger>
                <TabsTrigger value="orders" className="gap-2">
                  <Package className="h-4 w-4" />
                  <span className="hidden sm:inline">Заказы</span>
                </TabsTrigger>
                {user.isPartner && (
                  <TabsTrigger value="referral" className="gap-2">
                    <Gift className="h-4 w-4" />
                    <span className="hidden sm:inline">Рефералы</span>
                  </TabsTrigger>
                )}
              </TabsList>

              {/* Profile Tab */}
              <TabsContent value="profile">
                <Card className="border-0 shadow-premium">
                  <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                      <CardTitle>Личные данные</CardTitle>
                      <CardDescription>Управление профилем</CardDescription>
                    </div>
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => isEditing ? handleSaveProfile() : setIsEditing(true)}
                    >
                      {isEditing ? <Check className="h-4 w-4 mr-2" /> : <Edit className="h-4 w-4 mr-2" />}
                      {isEditing ? 'Сохранить' : 'Редактировать'}
                    </Button>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Имя</Label>
                        {isEditing ? (
                          <Input 
                            value={editName} 
                            onChange={(e) => setEditName(e.target.value)} 
                          />
                        ) : (
                          <div className="flex items-center gap-2 p-2 bg-muted/50 rounded-md">
                            <User className="h-4 w-4 text-muted-foreground" />
                            {user.name}
                          </div>
                        )}
                      </div>
                      
                      <div className="space-y-2">
                        <Label>Email</Label>
                        <div className="flex items-center gap-2 p-2 bg-muted/50 rounded-md">
                          <Mail className="h-4 w-4 text-muted-foreground" />
                          {user.email}
                        </div>
                      </div>
                      
                      <div className="space-y-2">
                        <Label>Телефон</Label>
                        {isEditing ? (
                          <Input 
                            value={editPhone} 
                            onChange={(e) => setEditPhone(e.target.value)}
                            placeholder="+7 (999) 123-45-67"
                          />
                        ) : (
                          <div className="flex items-center gap-2 p-2 bg-muted/50 rounded-md">
                            <Phone className="h-4 w-4 text-muted-foreground" />
                            {user.phone || 'Не указан'}
                          </div>
                        )}
                      </div>
                      
                      <div className="space-y-2">
                        <Label>Дата регистрации</Label>
                        <div className="flex items-center gap-2 p-2 bg-muted/50 rounded-md">
                          {new Date(user.createdAt).toLocaleDateString('ru-RU')}
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Orders Tab */}
              <TabsContent value="orders">
                <Card className="border-0 shadow-premium">
                  <CardHeader>
                    <CardTitle>История заказов</CardTitle>
                    <CardDescription>Все ваши заказы</CardDescription>
                  </CardHeader>
                  <CardContent>
                    {orders.length === 0 ? (
                      <div className="text-center py-12">
                        <Package className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <p className="text-muted-foreground mb-4">У вас пока нет заказов</p>
                        <Button asChild>
                          <Link to="/catalog">Перейти в каталог</Link>
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {orders.map((order) => {
                          const status = getStatusBadge(order.status);
                          const isPaid = order.paymentStatus === 'completed' || order.paidAt !== null;
                          return (
                            <div 
                              key={order.id}
                              className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
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
                                  {new Date(order.createdAt).toLocaleDateString('ru-RU')} • {order.total.toLocaleString()} ₽
                                </p>
                                {order.trackingNumber && (
                                  <p className="text-sm text-muted-foreground">
                                    Трекинг: {order.trackingNumber}
                                  </p>
                                )}
                              </div>
                              <ChevronRight className="h-5 w-5 text-muted-foreground" />
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Referral Tab - только для партнеров */}
              {user.isPartner && (
                <TabsContent value="referral">
                  <div className="grid gap-6">
                    <Card className="border-0 shadow-premium">
                      <CardHeader>
                        <CardTitle>Реферальная программа</CardTitle>
                        <CardDescription>
                          Приглашайте друзей и получайте {referralInfo?.referralPercent || 5}% от их покупок
                        </CardDescription>
                      </CardHeader>
                      <CardContent className="space-y-6">
                        <div className="p-4 bg-primary/5 rounded-lg border border-primary/20">
                          <Label className="text-sm font-medium">Ваша реферальная ссылка</Label>
                          <div className="flex gap-2 mt-2">
                            <Input 
                              value={`${window.location.origin}?ref=${user.id}`}
                              readOnly
                              className="bg-background"
                            />
                            <Button onClick={copyReferralLink} variant="outline">
                              {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                            </Button>
                          </div>
                        </div>
                        
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                          <div className="text-center p-4 bg-muted/50 rounded-lg">
                            <p className="text-2xl font-bold text-green-600">{referralInfo?.totalEarnings || 0} ₽</p>
                            <p className="text-sm text-muted-foreground">Заработано всего</p>
                          </div>
                          <div className="text-center p-4 bg-muted/50 rounded-lg">
                            <p className="text-2xl font-bold text-accent">{referralInfo?.pendingEarnings || 0} ₽</p>
                            <p className="text-sm text-muted-foreground">Ожидает начисления</p>
                          </div>
                        </div>
                      </CardContent>
                    </Card>

                    {/* Заказы рефералов */}
                    <Card className="border-0 shadow-premium">
                      <CardHeader>
                        <CardTitle>Заказы рефералов</CardTitle>
                        <CardDescription>Все заказы ваших приглашенных пользователей</CardDescription>
                      </CardHeader>
                      <CardContent>
                        {referralOrders.length === 0 ? (
                          <div className="text-center py-12">
                            <Package className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                            <p className="text-muted-foreground">У ваших рефералов пока нет заказов</p>
                          </div>
                        ) : (
                          <div className="space-y-4">
                            {referralOrders.map((order) => {
                              const status = getStatusBadge(order.status);
                              const isPaid = order.paymentStatus === 'completed' || order.paidAt !== null;
                              // Рассчитываем потенциальный или фактический заработок
                              const referralPercent = referralInfo?.referralPercent || 5;
                              const earnedAmount = isPaid 
                                ? (order.bonusEarned || 0)
                                : Math.floor((order.total || 0) * referralPercent / 100);
                              const showEarnings = earnedAmount > 0;
                              
                              return (
                                <div 
                                  key={order.id}
                                  className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
                                  onClick={() => setSelectedOrder(order)}
                                >
                                  <div className="flex-1">
                                    <div className="flex items-center gap-3 mb-1">
                                      <span className="font-medium">Заказ #{order.id}</span>
                                      <Badge variant={status.variant}>{status.label}</Badge>
                                      <Badge variant={isPaid ? 'default' : 'outline'} className={isPaid ? 'bg-green-500 hover:bg-green-600' : ''}>
                                        {isPaid ? 'Оплачен' : 'Не оплачен'}
                                      </Badge>
                                      {showEarnings && (
                                        <Badge 
                                          variant="outline" 
                                          className={isPaid ? 'bg-green-100 text-green-700 border-green-300' : 'bg-orange-100 text-orange-700 border-orange-300'}
                                        >
                                          +{earnedAmount.toLocaleString()} ₽
                                        </Badge>
                                      )}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                      {new Date(order.createdAt).toLocaleDateString('ru-RU')} • {order.total.toLocaleString()} ₽
                                    </p>
                                    {order.trackingNumber && (
                                      <p className="text-sm text-muted-foreground">
                                        Трекинг: {order.trackingNumber}
                                      </p>
                                    )}
                                  </div>
                                  <ChevronRight className="h-5 w-5 text-muted-foreground" />
                                </div>
                              );
                            })}
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>
              )}
            </Tabs>
          </motion.div>
        </div>
      </main>
      
      <Footer />

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
                {selectedOrder.items && Array.isArray(selectedOrder.items) && selectedOrder.items.length > 0 ? (
                  <div className="space-y-2">
                    {selectedOrder.items.map((item: any, index: number) => (
                      <div key={`${item?.productId || index}-${index}`} className="flex justify-between items-center p-2 bg-muted/50 rounded">
                        <div>
                          <p className="font-medium">{item?.productName || 'Товар'}</p>
                          <p className="text-sm text-muted-foreground">{(item?.quantity || 0)} × {((item?.price || 0)).toLocaleString()} ₽</p>
                        </div>
                        <p className="font-medium">{((item?.price || 0) * (item?.quantity || 0)).toLocaleString()} ₽</p>
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
                    <p className="text-muted-foreground">Использовано бонусов: {(selectedOrder.bonusUsed || 0).toLocaleString()} ₽</p>
                  )}
                  {selectedOrder.trackingNumber && (
                    <p className="text-muted-foreground">Трекинг: {selectedOrder.trackingNumber}</p>
                  )}
                </div>
                <p className="text-xl font-bold">{(selectedOrder.total || 0).toLocaleString()} ₽</p>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default Profile;
