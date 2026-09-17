import {
  FileText,
  FileCheck2,
  HelpCircle,
  LayoutDashboard,
  LogOut,
  Menu,
  Store,
  Settings,
  Star,
  StickyNote,
  ShoppingCart,
  Users,
  Wallet,
  X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Navigate, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { getStoredAdmin, getToken, logout, sessionClearedEvent } from '../api/client';

const links = [
  { to: '/admin/program', label: 'Партнёрская программа', icon: Users },
  { to: '/admin', label: 'Дашборд', icon: LayoutDashboard },
  { to: '/admin/shop', label: 'Магазин', icon: Store },
  { to: '/admin/pages', label: 'Страницы', icon: StickyNote },
  { to: '/admin/orders', label: 'Заказы', icon: ShoppingCart },
  { to: '/admin/certificates', label: 'Сертификаты', icon: FileCheck2 },
  { to: '/admin/faq', label: 'FAQ', icon: HelpCircle },
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
  const mobileMenuRef = useRef<HTMLDivElement>(null);
  const menuButtonRef = useRef<HTMLButtonElement>(null);
  const adminRoot = document.getElementById('admin-root');
  const brandName = adminRoot?.dataset.brandName || 'БИОФАРМ';
  const brandLogoUrl = adminRoot?.dataset.brandLogoUrl || '/uploads/images/logo.png';

  useEffect(() => {
    const handleSessionCleared = () => {
      setAdmin(null);
      navigate('/admin/login', { replace: true });
    };

    window.addEventListener(sessionClearedEvent, handleSessionCleared);

    return () => window.removeEventListener(sessionClearedEvent, handleSessionCleared);
  }, [navigate]);

  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const previousOverflow = document.body.style.overflow;
    const menuButton = menuButtonRef.current;
    const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
    document.body.style.overflow = 'hidden';
    const animationFrame = window.requestAnimationFrame(() => {
      mobileMenuRef.current?.querySelector<HTMLElement>(focusableSelector)?.focus();
    });
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
        return;
      }
      if (event.key !== 'Tab' || !mobileMenuRef.current) return;
      const focusable = Array.from(mobileMenuRef.current.querySelectorAll<HTMLElement>(focusableSelector));
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      window.cancelAnimationFrame(animationFrame);
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = previousOverflow;
      menuButton?.focus();
    };
  }, [open]);

  if (!admin || !getToken()) {
    return <Navigate to="/admin/login" replace />;
  }

  const adminName = admin?.first_name || 'Администратор';
  const adminEmail = admin?.email || 'admin@biofarm.local';

  const signOut = () => {
    void logout().finally(() => navigate('/admin/login'));
  };

  const sidebar = (
    <aside className="flex h-full min-h-0 w-64 flex-col border-r border-[#dfece9] bg-[#eaf5f1]">
      <div className="flex h-16 items-center border-b border-[#dfece9] px-4">
        <a href="/admin" className="inline-flex min-w-0 items-center gap-3" aria-label={`${brandName}: админ-панель`}>
          <img src={brandLogoUrl} alt="" className="h-9 w-auto max-w-[150px] object-contain [filter:brightness(0)_saturate(100%)_invert(29%)_sepia(20%)_saturate(1250%)_hue-rotate(122deg)_brightness(89%)_contrast(91%)]" />
        </a>
      </div>
      <nav className="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Разделы админ-панели">
        {links.map((link) => {
          const Icon = link.icon;

          return (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.to === '/admin'}
              onClick={() => setOpen(false)}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                  isActive
                    ? 'bg-white text-[#18574f] shadow-sm ring-1 ring-[#dfece9]'
                    : 'text-[#526d78] hover:bg-white/70 hover:text-[#18574f]'
                }`
              }
            >
              <Icon className="h-5 w-5" />
              {link.label}
            </NavLink>
          );
        })}
      </nav>
      <div className="border-t border-[#dfece9] p-4">
        <div className="mb-3 flex items-center gap-3">
          <span className="grid h-10 w-10 place-items-center rounded-full bg-[#eaf5f1] text-[#2e8175]">
            <Users className="h-5 w-5" />
          </span>
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-[#294555]">{adminName}</p>
            <p className="truncate text-xs text-[#526d78]">{adminEmail}</p>
          </div>
        </div>
        <button
          type="button"
          className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-[#cfe2de] bg-white text-sm font-semibold text-[#294555] transition hover:bg-[#f8f7f0]"
          onClick={signOut}
        >
          <LogOut className="h-4 w-4" />
          Выйти
        </button>
      </div>
    </aside>
  );

  return (
    <div className="min-h-screen bg-white text-[#294555]">
      <div className="fixed inset-y-0 left-0 z-30 hidden lg:block">{sidebar}</div>
      <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-[#dfece9] bg-[#eaf5f1] px-4 lg:hidden">
        <a href="/admin" aria-label={`${brandName}: админ-панель`}><img src={brandLogoUrl} alt="" className="h-9 w-auto max-w-[150px] object-contain [filter:brightness(0)_saturate(100%)_invert(29%)_sepia(20%)_saturate(1250%)_hue-rotate(122deg)_brightness(89%)_contrast(91%)]" /></a>
        <button
          ref={menuButtonRef}
          type="button"
          className="grid h-10 w-10 place-items-center rounded-md border border-[#cfe2de] bg-white"
          onClick={() => setOpen(true)}
          aria-label="Открыть меню"
        >
          <Menu className="h-5 w-5" />
        </button>
      </header>
      {open && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button type="button" aria-label="Закрыть меню" className="absolute inset-0 bg-[#101812]/55" onClick={() => setOpen(false)} />
          <div ref={mobileMenuRef} role="dialog" aria-modal="true" aria-label="Меню админ-панели" className="relative h-full w-64" tabIndex={-1}>
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
        <main className="w-full min-w-0 p-4 sm:p-6 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
