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
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { useAuthContext } from '@/contexts/AuthContext';
import { authApi, ReferralInfo } from '@/data/users';
import { ordersApi, Order } from '@/data/orders';
import { useToast } from '@/hooks/use-toast';

const Profile = () => {
  const navigate = useNavigate();
  const { user, logout, updateProfile, isAuthenticated } = useAuthContext();
  const { toast } = useToast();
  
  const [orders, setOrders] = useState<Order[]>([]);
  const [referralInfo, setReferralInfo] = useState<ReferralInfo | null>(null);
  const [copied, setCopied] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState('');
  const [editPhone, setEditPhone] = useState('');

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login');
      return;
    }
    
    if (user) {
      setEditName(user.name);
      setEditPhone(user.phone || '');
      
      ordersApi.getOrders(user.id).then(setOrders);
      authApi.getReferralInfo(user.id).then(setReferralInfo);
    }
  }, [user, isAuthenticated, navigate]);

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
                <TabsTrigger value="referral" className="gap-2">
                  <Gift className="h-4 w-4" />
                  <span className="hidden sm:inline">Рефералы</span>
                </TabsTrigger>
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
                          return (
                            <div 
                              key={order.id}
                              className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors"
                            >
                              <div className="flex-1">
                                <div className="flex items-center gap-3 mb-1">
                                  <span className="font-medium">{order.id}</span>
                                  <Badge variant={status.variant}>{status.label}</Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                  {new Date(order.createdAt).toLocaleDateString('ru-RU')} • {order.total} ₽
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

              {/* Referral Tab */}
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
                      
                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div className="text-center p-4 bg-muted/50 rounded-lg">
                          <p className="text-2xl font-bold text-primary">{referralInfo?.referredUsers || 0}</p>
                          <p className="text-sm text-muted-foreground">Приглашено</p>
                        </div>
                        <div className="text-center p-4 bg-muted/50 rounded-lg">
                          <p className="text-2xl font-bold text-green-600">{referralInfo?.totalEarnings || 0} ₽</p>
                          <p className="text-sm text-muted-foreground">Заработано всего</p>
                        </div>
                        <div className="text-center p-4 bg-muted/50 rounded-lg">
                          <p className="text-2xl font-bold text-accent">{referralInfo?.pendingEarnings || 0} ₽</p>
                          <p className="text-sm text-muted-foreground">Ожидает начисления</p>
                        </div>
                      </div>
                      
                      <div className="p-4 bg-accent/10 rounded-lg">
                        <h4 className="font-medium mb-2">Как это работает?</h4>
                        <ol className="text-sm text-muted-foreground space-y-1 list-decimal list-inside">
                          <li>Поделитесь ссылкой с друзьями</li>
                          <li>Друг регистрируется и делает покупку</li>
                          <li>Вы получаете {referralInfo?.referralPercent || 5}% от суммы его заказа</li>
                          <li>Бонусы можно использовать для оплаты заказов</li>
                        </ol>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>
            </Tabs>
          </motion.div>
        </div>
      </main>
      
      <Footer />
    </div>
  );
};

export default Profile;
