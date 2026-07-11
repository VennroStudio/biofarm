import {
  Check,
  ChevronRight,
  Copy,
  Edit,
  Gift,
  LogOut,
  Mail,
  Package,
  Phone,
  User,
  Users,
  Wallet,
} from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, type ReactNode, useEffect, useMemo, useState } from 'react';
import {
  clearAuth,
  createWithdrawal,
  getOrders,
  getReferralInfo,
  getReferralOrders,
  getStoredUser,
  getToken,
  getWithdrawals,
  refreshUser,
  updateProfile,
  type ReferralInfo,
  type SiteOrder,
  type SiteUser,
  type WithdrawalRequest,
} from '../../site/api';
import { formatDate, formatMoney } from '../../site/format';
import {
  Badge,
  Button,
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Input,
  Label,
  LinkButton,
  cn,
} from '../../site/ui';

type Tab = 'orders' | 'profile' | 'referral';

const orderStatus = {
  cancelled: { label: 'Отменен', variant: 'destructive' },
  delivered: { label: 'Доставлен', variant: 'default' },
  pending: { label: 'Ожидает', variant: 'secondary' },
  processing: { label: 'Обработка', variant: 'default' },
  shipped: { label: 'Доставляется', variant: 'default' },
} as const;

function TabButton({ active, children, onClick }: { active: boolean; children: ReactNode; onClick: () => void }) {
  return (
    <button
      className={cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all',
        active ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
      )}
      type="button"
      onClick={onClick}
    >
      {children}
    </button>
  );
}

function OrderBadge({ order }: { order: SiteOrder }) {
  const status = orderStatus[order.status] || orderStatus.pending;

  return <Badge variant={status.variant === 'destructive' ? 'outline' : status.variant}>{status.label}</Badge>;
}

function isPaid(order: SiteOrder) {
  return order.paymentStatus === 'completed' || order.paidAt !== null;
}

function OrderDetailsDialog({ order, onClose }: { order: SiteOrder | null; onClose: () => void }) {
  if (!order) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
      <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-background p-6 shadow-premium-lg">
        <div className="mb-6 flex items-center justify-between gap-4">
          <h2 className="text-xl font-bold">Заказ {order.id}</h2>
          <Button size="sm" variant="outline" onClick={onClose}>Закрыть</Button>
        </div>

        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <h4 className="mb-2 font-medium">Клиент</h4>
              <p>{order.shippingAddress.name || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.phone || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.email || 'Не указано'}</p>
            </div>
            <div>
              <h4 className="mb-2 font-medium">Адрес доставки</h4>
              <p>{order.shippingAddress.city || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.address || 'Не указано'}</p>
              <p className="text-muted-foreground">{order.shippingAddress.postalCode || 'Не указано'}</p>
            </div>
          </div>

          <div>
            <h4 className="mb-2 font-medium">Товары</h4>
            {order.items.length > 0 ? (
              <div className="space-y-2">
                {order.items.map((item) => (
                  <div className="flex items-center justify-between rounded bg-muted/50 p-2" key={`${item.productId}-${item.productName}`}>
                    <div>
                      <p className="font-medium">{item.productName}</p>
                      <p className="text-sm text-muted-foreground">
                        {item.quantity} x {formatMoney(item.price)}
                      </p>
                    </div>
                    <p className="font-medium">{formatMoney(item.price * item.quantity)}</p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-muted-foreground">Товары не загружены</p>
            )}
          </div>

          <div className="flex items-center justify-between border-t pt-4">
            <div>
              <p className="text-muted-foreground">Способ оплаты: {order.paymentMethod || 'Не указано'}</p>
              {order.bonusUsed > 0 && <p className="text-muted-foreground">Использовано бонусов: {formatMoney(order.bonusUsed)}</p>}
              {order.trackingNumber && <p className="text-muted-foreground">Трекинг: {order.trackingNumber}</p>}
            </div>
            <p className="text-xl font-bold">{formatMoney(order.total)}</p>
          </div>
        </div>
      </div>
    </div>
  );
}

function ProfilePage() {
  const [user, setUser] = useState<SiteUser | null>(() => getStoredUser());
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState<Tab>('profile');
  const [orders, setOrders] = useState<SiteOrder[]>([]);
  const [referralOrders, setReferralOrders] = useState<SiteOrder[]>([]);
  const [referralInfo, setReferralInfo] = useState<ReferralInfo | null>(null);
  const [withdrawals, setWithdrawals] = useState<WithdrawalRequest[]>([]);
  const [withdrawalAmount, setWithdrawalAmount] = useState('');
  const [copied, setCopied] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState(user?.name || '');
  const [editPhone, setEditPhone] = useState(user?.phone || '');
  const [editCardNumber, setEditCardNumber] = useState(user?.cardNumber || '');
  const [selectedOrder, setSelectedOrder] = useState<SiteOrder | null>(null);
  const [notice, setNotice] = useState('');

  const referralCode = useMemo(
    () => referralInfo?.referralCode || user?.referralCode || user?.id || '',
    [referralInfo?.referralCode, user?.id, user?.referralCode],
  );

  useEffect(() => {
    if (!getToken()) {
      window.location.href = '/login?redirect=/profile';
      return;
    }

    void Promise.all([refreshUser(), getOrders()])
      .then(async ([freshUser, loadedOrders]) => {
        if (!freshUser) {
          window.location.href = '/login?redirect=/profile';
          return;
        }

        setUser(freshUser);
        setEditName(freshUser.name);
        setEditPhone(freshUser.phone || '');
        setEditCardNumber(freshUser.cardNumber || '');
        setOrders(loadedOrders);

        if (freshUser.isPartner) {
          const [info, refOrders, userWithdrawals] = await Promise.all([
            getReferralInfo(),
            getReferralOrders(),
            getWithdrawals(),
          ]);
          setReferralInfo(info);
          setReferralOrders(refOrders);
          setWithdrawals(userWithdrawals);
        }
      })
      .finally(() => setLoading(false));
  }, []);

  function handleLogout() {
    clearAuth();
    window.location.href = '/';
  }

  async function copyReferralLink() {
    const link = `${window.location.origin}?ref=${referralCode}`;
    await navigator.clipboard.writeText(link);
    setCopied(true);
    setNotice('Ссылка скопирована!');
    window.setTimeout(() => setCopied(false), 2000);
  }

  async function handleSaveProfile() {
    const updated = await updateProfile({ cardNumber: editCardNumber, name: editName, phone: editPhone });
    if (updated) {
      setUser(updated);
      setIsEditing(false);
      setNotice('Профиль обновлен');
    }
  }

  async function handleWithdrawal(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const amount = Number(withdrawalAmount);
    if (!Number.isFinite(amount) || amount <= 0) {
      setNotice('Укажите сумму вывода');
      return;
    }

    await createWithdrawal(amount);
    const [freshUser, userWithdrawals] = await Promise.all([refreshUser(), getWithdrawals()]);
    if (freshUser) {
      setUser(freshUser);
    }
    setWithdrawals(userWithdrawals);
    setWithdrawalAmount('');
    setNotice('Заявка на вывод создана');
  }

  if (loading) {
    return (
      <section className="flex min-h-screen items-center justify-center bg-secondary/30 pt-24">
        <p className="text-muted-foreground">Загрузка...</p>
      </section>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <section className="min-h-screen bg-secondary/30 pb-8 pt-24 md:pb-12 md:pt-28">
      <div className="container mx-auto px-4">
        <div className="mb-8 flex items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold md:text-3xl">Личный кабинет</h1>
            <p className="text-muted-foreground">Добро пожаловать, {user.name}!</p>
          </div>
          <Button variant="outline" onClick={handleLogout}>
            <LogOut className="h-4 w-4" />
            Выйти
          </Button>
        </div>

        {notice && <p className="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">{notice}</p>}

        <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
          <Card className="border-0 shadow-premium">
            <CardContent className="flex items-center gap-4 p-6">
              <div className="rounded-full bg-primary/10 p-3">
                <Wallet className="h-6 w-6 text-primary" />
              </div>
              <div>
                <p className="text-2xl font-bold">{formatMoney(user.bonusBalance)}</p>
                <p className="text-sm text-muted-foreground">Бонусный баланс</p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-premium">
            <CardContent className="flex items-center gap-4 p-6">
              <div className="rounded-full bg-accent/10 p-3">
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
                <div className="rounded-full bg-green-100 p-3">
                  <Users className="h-6 w-6 text-green-600" />
                </div>
                <div>
                  <p className="text-2xl font-bold">{referralInfo?.referredUsers || 0}</p>
                  <p className="text-sm text-muted-foreground">Приглашенных</p>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        <div className="space-y-6">
          <div className="inline-flex rounded-md border bg-card p-1 shadow-sm">
            <TabButton active={tab === 'profile'} onClick={() => setTab('profile')}>
              <User className="h-4 w-4" />
              <span className="hidden sm:inline">Профиль</span>
            </TabButton>
            <TabButton active={tab === 'orders'} onClick={() => setTab('orders')}>
              <Package className="h-4 w-4" />
              <span className="hidden sm:inline">Заказы</span>
            </TabButton>
            {user.isPartner && (
              <TabButton active={tab === 'referral'} onClick={() => setTab('referral')}>
                <Gift className="h-4 w-4" />
                <span className="hidden sm:inline">Рефералы</span>
              </TabButton>
            )}
          </div>

          {tab === 'profile' && (
            <Card className="border-0 shadow-premium">
              <CardHeader className="flex flex-row items-center justify-between">
                <div>
                  <CardTitle>Личные данные</CardTitle>
                  <CardDescription>Управление профилем</CardDescription>
                </div>
                <Button size="sm" variant="outline" onClick={() => (isEditing ? void handleSaveProfile() : setIsEditing(true))}>
                  {isEditing ? <Check className="h-4 w-4" /> : <Edit className="h-4 w-4" />}
                  {isEditing ? 'Сохранить' : 'Редактировать'}
                </Button>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Имя</Label>
                    {isEditing ? (
                      <Input value={editName} onChange={(event) => setEditName(event.target.value)} />
                    ) : (
                      <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
                        <User className="h-4 w-4 text-muted-foreground" />
                        {user.name}
                      </div>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label>Email</Label>
                    <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      {user.email}
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label>Телефон</Label>
                    {isEditing ? (
                      <Input placeholder="+7 (999) 123-45-67" value={editPhone} onChange={(event) => setEditPhone(event.target.value)} />
                    ) : (
                      <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
                        <Phone className="h-4 w-4 text-muted-foreground" />
                        {user.phone || 'Не указан'}
                      </div>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label>Дата регистрации</Label>
                    <div className="rounded-md bg-muted/50 p-2">{formatDate(user.createdAt)}</div>
                  </div>

                  {user.isPartner && (
                    <div className="space-y-2 md:col-span-2">
                      <Label>Карта для выплат</Label>
                      {isEditing ? (
                        <Input value={editCardNumber} onChange={(event) => setEditCardNumber(event.target.value)} />
                      ) : (
                        <div className="rounded-md bg-muted/50 p-2">{user.cardNumber || 'Не указана'}</div>
                      )}
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          )}

          {tab === 'orders' && (
            <Card className="border-0 shadow-premium">
              <CardHeader>
                <CardTitle>История заказов</CardTitle>
                <CardDescription>Все ваши заказы</CardDescription>
              </CardHeader>
              <CardContent>
                {orders.length === 0 ? (
                  <div className="py-12 text-center">
                    <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                    <p className="mb-4 text-muted-foreground">У вас пока нет заказов</p>
                    <LinkButton href="/catalog">Перейти в каталог</LinkButton>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {orders.map((order) => (
                      <button
                        className="flex w-full cursor-pointer items-center justify-between rounded-lg border p-4 text-left transition-colors hover:bg-muted/50"
                        key={order.id}
                        type="button"
                        onClick={() => setSelectedOrder(order)}
                      >
                        <div className="flex-1">
                          <div className="mb-1 flex flex-wrap items-center gap-3">
                            <span className="font-medium">{order.id}</span>
                            <OrderBadge order={order} />
                            <Badge className={isPaid(order) ? 'bg-green-500 hover:bg-green-600' : ''} variant={isPaid(order) ? 'default' : 'outline'}>
                              {isPaid(order) ? 'Оплачен' : 'Не оплачен'}
                            </Badge>
                          </div>
                          <p className="text-sm text-muted-foreground">
                            {formatDate(order.createdAt)} • {formatMoney(order.total)}
                          </p>
                          {order.trackingNumber && <p className="text-sm text-muted-foreground">Трекинг: {order.trackingNumber}</p>}
                        </div>
                        <ChevronRight className="h-5 w-5 text-muted-foreground" />
                      </button>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {user.isPartner && tab === 'referral' && (
            <div className="grid gap-6">
              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle>Реферальная программа</CardTitle>
                  <CardDescription>
                    Приглашайте друзей и получайте {referralInfo?.referralPercent || 5}% от их покупок
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="rounded-lg border border-primary/20 bg-primary/5 p-4">
                    <Label className="text-sm font-medium">Ваша реферальная ссылка</Label>
                    <div className="mt-2 flex gap-2">
                      <Input className="bg-background" readOnly value={`${window.location.origin}?ref=${referralCode}`} />
                      <Button variant="outline" onClick={() => void copyReferralLink()}>
                        {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                      </Button>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="rounded-lg bg-muted/50 p-4 text-center">
                      <p className="text-2xl font-bold text-green-600">{formatMoney(referralInfo?.totalEarnings || 0)}</p>
                      <p className="text-sm text-muted-foreground">Заработано всего</p>
                    </div>
                    <div className="rounded-lg bg-muted/50 p-4 text-center">
                      <p className="text-2xl font-bold text-accent">{formatMoney(referralInfo?.pendingEarnings || 0)}</p>
                      <p className="text-sm text-muted-foreground">Ожидает начисления</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle>Вывод бонусов</CardTitle>
                  <CardDescription>Создайте заявку на выплату партнерских начислений</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <form className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]" onSubmit={handleWithdrawal}>
                    <Input
                      min="1"
                      placeholder="Сумма"
                      type="number"
                      value={withdrawalAmount}
                      onChange={(event) => setWithdrawalAmount(event.target.value)}
                    />
                    <Button type="submit">Создать заявку</Button>
                  </form>
                  {withdrawals.length > 0 && (
                    <div className="space-y-2">
                      {withdrawals.map((withdrawal) => (
                        <div className="flex items-center justify-between rounded border p-3" key={withdrawal.id}>
                          <span>{formatMoney(withdrawal.amount)}</span>
                          <span className="text-sm text-muted-foreground">{formatDate(withdrawal.createdAt)}</span>
                          <Badge variant={withdrawal.status === 'approved' ? 'default' : 'secondary'}>{withdrawal.status}</Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card className="border-0 shadow-premium">
                <CardHeader>
                  <CardTitle>Заказы рефералов</CardTitle>
                  <CardDescription>Все заказы ваших приглашенных пользователей</CardDescription>
                </CardHeader>
                <CardContent>
                  {referralOrders.length === 0 ? (
                    <div className="py-12 text-center">
                      <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                      <p className="text-muted-foreground">У ваших рефералов пока нет заказов</p>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {referralOrders.map((order) => {
                        const earned = isPaid(order)
                          ? order.bonusEarned
                          : Math.floor(order.total * ((referralInfo?.referralPercent || 5) / 100));

                        return (
                          <button
                            className="flex w-full cursor-pointer items-center justify-between rounded-lg border p-4 text-left transition-colors hover:bg-muted/50"
                            key={order.id}
                            type="button"
                            onClick={() => setSelectedOrder(order)}
                          >
                            <div className="flex-1">
                              <div className="mb-1 flex flex-wrap items-center gap-3">
                                <span className="font-medium">Заказ #{order.id}</span>
                                <OrderBadge order={order} />
                                <Badge className={isPaid(order) ? 'bg-green-500 hover:bg-green-600' : ''} variant={isPaid(order) ? 'default' : 'outline'}>
                                  {isPaid(order) ? 'Оплачен' : 'Не оплачен'}
                                </Badge>
                                {earned > 0 && (
                                  <Badge
                                    className={isPaid(order) ? 'border-green-300 bg-green-100 text-green-700' : 'border-orange-300 bg-orange-100 text-orange-700'}
                                    variant="outline"
                                  >
                                    +{formatMoney(earned)}
                                  </Badge>
                                )}
                              </div>
                              <p className="text-sm text-muted-foreground">
                                {formatDate(order.createdAt)} • {formatMoney(order.total)}
                              </p>
                            </div>
                            <ChevronRight className="h-5 w-5 text-muted-foreground" />
                          </button>
                        );
                      })}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          )}
        </div>
      </div>

      <OrderDetailsDialog order={selectedOrder} onClose={() => setSelectedOrder(null)} />
    </section>
  );
}

export function mountProfilePage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="profile-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') {
      return;
    }
    root.dataset.mounted = 'true';
    createRoot(root).render(<ProfilePage />);
  });
}
