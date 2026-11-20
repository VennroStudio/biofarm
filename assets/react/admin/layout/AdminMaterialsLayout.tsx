import { FileText, FolderTree } from 'lucide-react';
import { NavLink, Outlet } from 'react-router-dom';
import type { MaterialKind } from '../features/materials/types';
export function AdminMaterialsLayout({ kind }: { kind: MaterialKind }) {
  const title = kind === 'certificate' ? 'Сертификаты' : 'FAQ';
  const base = kind === 'certificate' ? 'certificates' : 'faq';
  if (kind === 'faq') return <Outlet />;
  return <><div className="mb-7"><p className="mb-3 text-sm font-semibold text-[#5f7580]">{title}</p><nav aria-label={`Разделы: ${title}`} className="flex flex-wrap gap-2 rounded-2xl border border-[#dfece9] bg-[#f4faf8] p-2">{[{ path: 'list', label: 'Список', icon: FileText }, { path: 'categories', label: 'Категории', icon: FolderTree }].map(({ path, label, icon: Icon }) => <NavLink key={path} to={`/admin/${base}/${path}`} className={({ isActive }) => `inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-colors focus-visible:outline-offset-2 focus-visible:outline-[#2e8175] ${isActive ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-white hover:text-[#18574f]'}`}><Icon className="h-4 w-4 shrink-0" aria-hidden="true" />{label}</NavLink>)}</nav></div><Outlet /></>;
}
