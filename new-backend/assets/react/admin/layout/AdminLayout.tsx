import {
  FileText,
  FolderTree,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Settings,
  SlidersHorizontal,
  Star,
  ShoppingCart,
  Users,
  Wallet,
  X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Navigate, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { getStoredAdmin, getToken, logout, sessionClearedEvent } from '../api/client';

const links = [
  { to: '/admin', label: 'Дашборд', icon: LayoutDashboard },
  { to: '/admin/products', label: 'Товары', icon: Package },
  { to: '/admin/categories', label: 'Категории', icon: FolderTree },
  { to: '/admin/attributes', label: 'Атрибуты', icon: SlidersHorizontal },
  { to: '/admin/orders', label: 'Заказы', icon: ShoppingCart },
  { to: '/admin/blog', label: 'Блог', icon: FileText },
  { to: '/admin/reviews', label: 'Отзывы', icon: Star },
  { to: '/admin/users', label: 'Пользователи', icon: Users },
  { to: '/admin/withdrawals', label: 'Заявки на вывод', icon: Wallet },
  { to: '/admin/settings', label: 'Настройки', icon: Settings },
];

export function AdminLayout() {
  const navigate = useNavigate();
  const [admin, setAdmin] = useState(() => getStoredAdmin());
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const handleSessionCleared = () => {
      setAdmin(null);
      navigate('/admin/login', { replace: true });
    };

    window.addEventListener(sessionClearedEvent, handleSessionCleared);

    return () => window.removeEventListener(sessionClearedEvent, handleSessionCleared);
  }, [navigate]);

  if (!admin || !getToken()) {
    return <Navigate to="/admin/login" replace />;
  }

  const adminName = admin?.first_name || 'Администратор';
  const adminEmail = admin?.email || 'admin@biofarm.local';

  const signOut = () => {
    void logout().finally(() => navigate('/admin/login'));
  };

  const sidebar = (
    <aside className="flex h-full w-64 flex-col border-r border-[#e4e5da] bg-[#fbfaf4]">
      <div className="flex h-16 items-center border-b border-[#e4e5da] px-4">
        <a href="/admin" className="text-xl font-bold text-[#1f6b3a]">BioFarm</a>
      </div>
      <nav className="flex-1 space-y-2 px-4 py-5">
        {links.map((link) => {
          const Icon = link.icon;
          return (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.to === '/admin'}
              onClick={() => setOpen(false)}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-md px-3 py-3 text-sm font-semibold transition ${
                  isActive
                    ? 'bg-[#1f6b3a] text-white'
                    : 'text-[#789083] hover:bg-[#eef1e8] hover:text-[#26382d]'
                }`
              }
            >
              <Icon className="h-5 w-5" />
              {link.label}
            </NavLink>
          );
        })}
      </nav>
      <div className="border-t border-[#e4e5da] p-4">
        <div className="mb-3 flex items-center gap-3">
          <span className="grid h-10 w-10 place-items-center rounded-full bg-[#e5f3e9] text-[#2f7d4b]">
            <Users className="h-5 w-5" />
          </span>
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-[#26382d]">{adminName}</p>
            <p className="truncate text-xs text-[#789083]">{adminEmail}</p>
          </div>
        </div>
        <button
          type="button"
          className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-[#d9dece] bg-white text-sm font-semibold text-[#26382d] transition hover:bg-[#f8f7f0]"
          onClick={signOut}
        >
          <LogOut className="h-4 w-4" />
          Выйти
        </button>
      </div>
    </aside>
  );

  return (
    <div className="min-h-screen bg-[#f6f5ee] text-[#26382d]">
      <div className="fixed inset-y-0 left-0 z-30 hidden lg:block">{sidebar}</div>
      <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-[#e4e5da] bg-[#fbfaf4] px-4 lg:hidden">
        <a href="/admin" className="text-xl font-bold text-[#1f6b3a]">BioFarm</a>
        <button
          type="button"
          className="grid h-10 w-10 place-items-center rounded-md border border-[#d9dece] bg-white"
          onClick={() => setOpen(true)}
          aria-label="Открыть меню"
        >
          <Menu className="h-5 w-5" />
        </button>
      </header>
      {open && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button type="button" aria-label="Закрыть меню" className="absolute inset-0 bg-[#101812]/55" onClick={() => setOpen(false)} />
          <div className="relative h-full">
            {sidebar}
            <button
              type="button"
              className="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-md bg-white"
              onClick={() => setOpen(false)}
              aria-label="Закрыть меню"
            >
              <X className="h-5 w-5" />
            </button>
          </div>
        </div>
      )}
      <div className="lg:ml-64">
        <main className="p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
