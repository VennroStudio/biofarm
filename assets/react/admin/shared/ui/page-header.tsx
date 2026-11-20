import type { ReactNode } from 'react';

export function PageHeader({ actions, subtitle, title }: { actions?: ReactNode; subtitle?: ReactNode; title: string }) {
  return (
    <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 className="text-2xl font-semibold text-[#2e8175] sm:text-3xl">{title}</h1>
        {subtitle && <p className="mt-1 text-[#5f7580]">{subtitle}</p>}
      </div>
      {actions}
    </div>
  );
}
