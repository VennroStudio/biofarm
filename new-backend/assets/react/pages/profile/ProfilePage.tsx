import { Gift, Heart, LogOut, MapPin, Package, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import {
  clearAuth,
  createWithdrawal,
  deleteUserAddress,
  getFavorites,
  getOrders,
  getReferralInfo,
  getReferralOrders,
  getStoredUser,
  getToken,
  getUserAddresses,
  getWithdrawals,
  refreshUser,
  saveUserAddress,
  updateProfile,
  type FavoriteProduct,
  type ReferralInfo,
  type ShippingAddress,
  type SiteOrder,
  type SiteUser,
  type UserAddress,
  type WithdrawalRequest,
} from '../../site/api';
import { loadFavoriteIds, toggleFavorite } from '../../site/favorites';
import { Button } from '../../site/ui';
import { AddressesPanel } from './components/AddressesPanel';
import { FavoritesPanel } from './components/FavoritesPanel';
import { OrderDetailsDialog } from './components/OrderDetailsDialog';
import { OrdersPanel } from './components/OrdersPanel';
import { ProfileDetailsCard } from './components/ProfileDetailsCard';
import { ProfileStats } from './components/ProfileStats';
import { TabButton } from './components/ProfileTabs';
import { ReferralPanel } from './components/ReferralPanel';
import type { ProfileTab } from './types';

function messageFromError(error: unknown, fallback: string): string {
  return error instanceof Error && error.message ? error.message : fallback;
}

type ProfilePageProps = {
  cartEnabled: boolean;
  favoritesEnabled: boolean;
  referralEnabled: boolean;
  withdrawalsEnabled: boolean;
};

function ProfilePage({ cartEnabled, favoritesEnabled, referralEnabled, withdrawalsEnabled }: ProfilePageProps) {
  const [user, setUser] = useState<SiteUser | null>(() => getStoredUser());
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState<ProfileTab>('profile');
  const [orders, setOrders] = useState<SiteOrder[]>([]);
  const [addresses, setAddresses] = useState<UserAddress[]>([]);
  const [favorites, setFavorites] = useState<FavoriteProduct[]>([]);
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

    void Promise.all([
      refreshUser(),
      cartEnabled ? getOrders() : Promise.resolve([]),
      cartEnabled ? getUserAddresses() : Promise.resolve([]),
    ])
      .then(async ([freshUser, loadedOrders, loadedAddresses]) => {
        if (!freshUser) {
          window.location.href = '/login?redirect=/profile';
          return;
        }

        setUser(freshUser);
        setEditName(freshUser.name);
        setEditPhone(freshUser.phone || '');
        setEditCardNumber(freshUser.cardNumber || '');
        setOrders(loadedOrders);
        setAddresses(loadedAddresses);

        if (favoritesEnabled) {
          await loadFavoriteIds();
          setFavorites(await getFavorites());
        }

        if (freshUser.isPartner && referralEnabled) {
          const [info, refOrders, userWithdrawals] = await Promise.all([
            getReferralInfo(),
            getReferralOrders(),
            withdrawalsEnabled ? getWithdrawals() : Promise.resolve([]),
          ]);
          setReferralInfo(info);
          setReferralOrders(refOrders);
          setWithdrawals(userWithdrawals);
        }
      })
      .catch((error: unknown) => {
        console.error('Failed to load profile page', error);
        setNotice(messageFromError(error, 'Не удалось загрузить личный кабинет'));
      })
      .finally(() => setLoading(false));
  }, [cartEnabled, favoritesEnabled, referralEnabled, withdrawalsEnabled]);

  function handleLogout() {
    clearAuth();
    window.location.href = '/';
  }

  async function copyReferralLink() {
    try {
      const link = `${window.location.origin}?ref=${referralCode}`;
      await navigator.clipboard.writeText(link);
      setCopied(true);
      setNotice('Ссылка скопирована!');
      window.setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось скопировать ссылку'));
    }
  }

  async function handleSaveProfile() {
    try {
      const updated = await updateProfile({ cardNumber: editCardNumber, name: editName, phone: editPhone });
      if (updated) {
        setUser(updated);
        setIsEditing(false);
        setNotice('Профиль обновлен');
      }
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось сохранить профиль'));
    }
  }

  async function handleWithdrawal(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const amount = Number(withdrawalAmount);
    if (!Number.isFinite(amount) || amount <= 0) {
      setNotice('Укажите сумму вывода');
      return;
    }

    try {
      await createWithdrawal(amount);
      const [freshUser, userWithdrawals] = await Promise.all([
        refreshUser(),
        withdrawalsEnabled ? getWithdrawals() : Promise.resolve([]),
      ]);
      if (freshUser) {
        setUser(freshUser);
      }
      setWithdrawals(userWithdrawals);
      setWithdrawalAmount('');
      setNotice('Заявка на вывод создана');
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось создать заявку на вывод'));
    }
  }

  async function handleRemoveFavorite(product: FavoriteProduct) {
    try {
      await toggleFavorite(product.id, true);
      setFavorites((items) => items.filter((item) => item.id !== product.id));
      setNotice('Товар удален из избранного');
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось удалить товар из избранного'));
    }
  }

  async function handleSaveAddress(address: ShippingAddress & { isDefault: boolean; label: string }, id?: number) {
    try {
      await saveUserAddress(address, id);
      setAddresses(await getUserAddresses());
      setNotice(id ? 'Адрес обновлен' : 'Адрес добавлен');
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось сохранить адрес'));
      throw error;
    }
  }

  async function handleDeleteAddress(id: number) {
    try {
      await deleteUserAddress(id);
      setAddresses((items) => items.filter((item) => item.id !== id));
      setNotice('Адрес удален');
    } catch (error) {
      setNotice(messageFromError(error, 'Не удалось удалить адрес'));
    }
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

        <ProfileStats orders={orders} referralInfo={referralInfo} user={user} />

        <div className="space-y-6">
          <div className="inline-flex rounded-md border bg-card p-1 shadow-sm">
            <TabButton active={tab === 'profile'} onClick={() => setTab('profile')}>
              <User className="h-4 w-4" />
              <span className="hidden sm:inline">Профиль</span>
            </TabButton>
            {cartEnabled && (
              <TabButton active={tab === 'orders'} onClick={() => setTab('orders')}>
                <Package className="h-4 w-4" />
                <span className="hidden sm:inline">Заказы</span>
              </TabButton>
            )}
            {cartEnabled && (
              <TabButton active={tab === 'addresses'} onClick={() => setTab('addresses')}>
                <MapPin className="h-4 w-4" />
                <span className="hidden sm:inline">Адреса</span>
              </TabButton>
            )}
            {user.isPartner && referralEnabled && (
              <TabButton active={tab === 'referral'} onClick={() => setTab('referral')}>
                <Gift className="h-4 w-4" />
                <span className="hidden sm:inline">Рефералы</span>
              </TabButton>
            )}
            {favoritesEnabled && (
              <TabButton active={tab === 'favorites'} onClick={() => setTab('favorites')}>
                <Heart className="h-4 w-4" />
                <span className="hidden sm:inline">Избранное</span>
              </TabButton>
            )}
          </div>

          {tab === 'profile' && (
            <ProfileDetailsCard
              editCardNumber={editCardNumber}
              editName={editName}
              editPhone={editPhone}
              isEditing={isEditing}
              setEditCardNumber={setEditCardNumber}
              setEditName={setEditName}
              setEditPhone={setEditPhone}
              user={user}
              onSave={() => void handleSaveProfile()}
              onStartEdit={() => setIsEditing(true)}
            />
          )}

          {cartEnabled && tab === 'orders' && <OrdersPanel orders={orders} onSelectOrder={setSelectedOrder} />}

          {cartEnabled && tab === 'addresses' && (
            <AddressesPanel
              addresses={addresses}
              user={user}
              onDelete={(id) => handleDeleteAddress(id)}
              onSave={(address, id) => handleSaveAddress(address, id)}
            />
          )}

          {favoritesEnabled && tab === 'favorites' && (
            <FavoritesPanel favorites={favorites} onRemove={(product) => void handleRemoveFavorite(product)} />
          )}

          {user.isPartner && referralEnabled && tab === 'referral' && (
            <ReferralPanel
              copied={copied}
              referralCode={referralCode}
              referralInfo={referralInfo}
              referralOrders={referralOrders}
              setWithdrawalAmount={setWithdrawalAmount}
              withdrawalAmount={withdrawalAmount}
              withdrawals={withdrawals}
              withdrawalsEnabled={withdrawalsEnabled}
              onCopyReferralLink={() => void copyReferralLink()}
              onSelectOrder={setSelectedOrder}
              onWithdrawal={(event) => void handleWithdrawal(event)}
            />
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
    createRoot(root).render(
      <ProfilePage
        cartEnabled={root.dataset.cartEnabled !== 'false'}
        favoritesEnabled={root.dataset.favoritesEnabled !== 'false'}
        referralEnabled={root.dataset.referralEnabled !== 'false'}
        withdrawalsEnabled={root.dataset.withdrawalsEnabled !== 'false'}
      />,
    );
  });
}
