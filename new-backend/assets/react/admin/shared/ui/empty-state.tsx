import type { PropsWithChildren } from 'react';

export function EmptyState({ children }: PropsWithChildren) {
  return <div className="rounded-lg border border-dashed border-[#d9dece] px-6 py-10 text-center text-[#789083]">{children}</div>;
}
