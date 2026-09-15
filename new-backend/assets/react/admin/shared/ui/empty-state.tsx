import type { PropsWithChildren } from 'react';

export function EmptyState({ children }: PropsWithChildren) {
  return <div className="rounded-lg border border-dashed border-[#cfe2de] px-6 py-10 text-center text-[#5f7580]">{children}</div>;
}
