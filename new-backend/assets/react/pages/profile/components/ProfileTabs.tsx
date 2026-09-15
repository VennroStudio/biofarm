import { type ReactNode } from 'react';
import { cn } from '../../../site/ui';

export function TabButton({ active, children, onClick }: { active: boolean; children: ReactNode; onClick: () => void }) {
  return (
    <button
      aria-selected={active}
      className={cn(
        'inline-flex min-h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors',
        active ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground',
      )}
      role="tab"
      type="button"
      onClick={onClick}
    >
      {children}
    </button>
  );
}
