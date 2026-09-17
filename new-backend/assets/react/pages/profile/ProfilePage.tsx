import { Gift, Heart, LogOut, MapPin, Package, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { useCallback, useEffect, useState } from 'react';
import {
  clearAuth,
  deleteUserAddress,
  getFavorites,
  getOrders,
  getStoredUser,
  getToken,
  getUserAddresses,
  refreshUser,
  saveUserAddress,
  updateProfile,
  type FavoriteProduct,
  type ShippingAddress,
  type SiteOrder,
  type SiteUser,
  type UserAddress,
} from '../../site/api';
import { loadFavoriteIds, toggleFavorite } from '../../site/favorites';
import { Button } from '../../site/ui';
import { BonusPanel } from './components/BonusPanel';
import { AddressesPanel } from './components/AddressesPanel';
import { FavoritesPanel } from './components/FavoritesPanel';
import { OrderDetailsDialog } from './components/OrderDetailsDialog';
import { OrdersPanel } from './components/OrdersPanel';
import { ProfileDetailsCard } from './components/ProfileDetailsCard';
import { TabButton, TabList, TabPanel } from './components/ProfileTabs';
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
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState(user?.name || '');
  const [editPhone, setEditPhone] = useState(user?.phone || '');
  const [editCardNumber, setEditCardNumber] = useState(user?.cardNumber || '');
  const [selectedOrder, setSelectedOrder] = useState<SiteOrder | null>(null);
  const [notice, setNotice] = useState('');

  const closeOrderDetails = useCallback(() => setSelectedOrder(null), []);

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


      })
      .catch((error: unknown) => {
        if (!getToken()) {
          window.location.href = '/login?redirect=/profile';
          return;
        }
        console.error('Failed to load profile page', error);
        setNotice(messageFromError(error, 'Не удалось загрузить личный кабинет'));
      })
      .finally(() => setLoading(false));
  }, [cartEnabled, favoritesEnabled, referralEnabled, withdrawalsEnabled]);

  function handleLogout() {
    clearAuth();
    window.location.href = '/';
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
      <section className="flex min-h-[60vh] items-center justify-center bg-secondary/30 pb-12 pt-[128px]">
        <p className="text-muted-foreground">Загрузка...</p>
      </section>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <section className="bg-secondary/30 pb-10 pt-[120px] md:pb-12 md:pt-[128px]">
      <div className="container mx-auto px-4 sm:px-6">
        <div className="mb-6 flex items-center justify-between gap-4">
          <div>
            <h1 className="text-3xl font-normal tracking-tight text-primary md:text-4xl">Личный кабинет</h1>
            <p className="text-muted-foreground">Добро пожаловать, {user.name}!</p>
          </div>
          <Button variant="outline" onClick={handleLogout}>
            <LogOut className="h-4 w-4" />
            Выйти
          </Button>
        </div>

        {notice && <p className="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">{notice}</p>}

        <div className="grid items-start gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
          <div className="min-w-0 space-y-3 lg:sticky lg:top-28">
            <TabList>
              <TabButton active={tab === 'profile'} controls="profile-tabpanel" id="profile-tab" onClick={() => setTab('profile')}>
                <User className="h-4 w-4" />
                <span>Профиль</span>
              </TabButton>
              {cartEnabled && (
                <TabButton active={tab === 'orders'} controls="orders-tabpanel" id="orders-tab" onClick={() => setTab('orders')}>
                  <Package className="h-4 w-4" />
                  <span>Заказы</span>
                </TabButton>
              )}
              {cartEnabled && (
                <TabButton active={tab === 'addresses'} controls="addresses-tabpanel" id="addresses-tab" onClick={() => setTab('addresses')}>
                  <MapPin className="h-4 w-4" />
                  <span>Адреса</span>
                </TabButton>
              )}
              <TabButton active={tab === 'bonuses'} controls="bonuses-tabpanel" id="bonuses-tab" onClick={() => setTab('bonuses')}>
                <Gift className="h-4 w-4" />
                <span>Мои бонусы</span>
              </TabButton>
              {favoritesEnabled && (
                <TabButton active={tab === 'favorites'} controls="favorites-tabpanel" id="favorites-tab" onClick={() => setTab('favorites')}>
                  <Heart className="h-4 w-4" />
                  <span>Избранное</span>
                </TabButton>
              )}
            </TabList>
            {referralEnabled && (user.isPartner || user.isTeamMember || user.hasCommissionHistory) && (
              <a href="/partner" className="flex items-center gap-3 rounded-2xl border border-border bg-white px-5 py-4 text-sm font-medium text-primary hover:bg-secondary focus-visible:outline-primary">
                <Gift className="h-4 w-4 shrink-0" />
                <span>{user.isPartner ? 'Кабинет партнёра' : user.isTeamMember ? 'Кабинет участника команды' : 'Начисления и выплаты'} →</span>
              </a>
            )}
          </div>

          <div className="min-w-0">
            <TabPanel active={tab === 'bonuses'} id="bonuses-tabpanel" labelledBy="bonuses-tab">
              {tab === 'bonuses' && <BonusPanel referralEnabled={referralEnabled} />}
            </TabPanel>
            <TabPanel active={tab === 'profile'} id="profile-tabpanel" labelledBy="profile-tab">
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
            </TabPanel>

            {cartEnabled && (
              <TabPanel active={tab === 'orders'} id="orders-tabpanel" labelledBy="orders-tab">
                <OrdersPanel orders={orders} onSelectOrder={setSelectedOrder} />
              </TabPanel>
            )}

            {cartEnabled && (
              <TabPanel active={tab === 'addresses'} id="addresses-tabpanel" labelledBy="addresses-tab">
                <AddressesPanel
                  addresses={addresses}
                  user={user}
                  onDelete={(id) => handleDeleteAddress(id)}
                  onSave={(address, id) => handleSaveAddress(address, id)}
                />
              </TabPanel>
            )}

            {favoritesEnabled && (
              <TabPanel active={tab === 'favorites'} id="favorites-tabpanel" labelledBy="favorites-tab">
                <FavoritesPanel favorites={favorites} onRemove={(product) => void handleRemoveFavorite(product)} />
              </TabPanel>
            )}
          </div>
        </div>
      </div>

      <OrderDetailsDialog order={selectedOrder} onClose={closeOrderDetails} />
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
