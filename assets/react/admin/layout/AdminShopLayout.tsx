import { FolderTree, Layers, Package, Percent, SlidersHorizontal } from 'lucide-react';
import { NavLink, Outlet } from 'react-router-dom';

const sections = [
  { path: 'categories', label: 'Категории', icon: FolderTree },
  { path: 'attributes', label: 'Атрибуты', icon: SlidersHorizontal },
  { path: 'products', label: 'Товары', icon: Package },
  { path: 'product-groups', label: 'Группы товаров', icon: Layers },
  { path: 'promo-codes', label: 'Промокоды', icon: Percent },
];

export function AdminShopLayout() {
  return (
    <>
      <div className="mb-7">
        <p className="mb-3 text-sm font-semibold text-[#5f7580]">Магазин</p>
        <nav aria-label="Разделы магазина" className="flex flex-wrap gap-2 rounded-2xl border border-[#dfece9] bg-[#f4faf8] p-2">
          {sections.map(({ path, label, icon: Icon }) => (
            <NavLink key={path} to={`/admin/shop/${path}`}
              className={({ isActive }) => `inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-colors focus-visible:outline-offset-2 focus-visible:outline-[#2e8175] ${isActive ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-white hover:text-[#18574f]'}`}>
              <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />{label}
            </NavLink>
          ))}
        </nav>
      </div>
      <Outlet />
    </>
  );
}
