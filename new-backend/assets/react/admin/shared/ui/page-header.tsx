import type { ReactNode } from 'react';

export function PageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: ReactNode; title: string }) {
  return (
    <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 className="text-3xl font-bold text-[#1f3328]">{title}</h1>
        {subtitle && <p className="mt-1 text-[#789083]">{subtitle}</p>}
      </div>
      {actions}
    </div>
  );
}
