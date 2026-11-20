import { type KeyboardEvent, type ReactNode, useEffect, useState } from 'react';
import { cn } from '../../../site/ui';

type Props = {
  active: boolean;
  children: ReactNode;
  controls: string;
  id: string;
  onClick: () => void;
};

function handleTabKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
  const vertical = event.currentTarget.parentElement?.getAttribute('aria-orientation') === 'vertical';
  const previousKey = vertical ? 'ArrowUp' : 'ArrowLeft';
  const nextKey = vertical ? 'ArrowDown' : 'ArrowRight';
  if (![previousKey, nextKey, 'Home', 'End'].includes(event.key)) {
    return;
  }

  const tabs = Array.from(event.currentTarget.parentElement?.querySelectorAll<HTMLButtonElement>('[role="tab"]') || []);
  const currentIndex = tabs.indexOf(event.currentTarget);
  if (currentIndex < 0 || tabs.length === 0) {
    return;
  }

  event.preventDefault();
  const nextIndex = event.key === 'Home'
    ? 0
    : event.key === 'End'
      ? tabs.length - 1
      : (currentIndex + (event.key === nextKey ? 1 : -1) + tabs.length) % tabs.length;
  tabs[nextIndex].focus({ preventScroll: vertical });
  tabs[nextIndex].click();
}

export function TabList({ children, label = "Разделы личного кабинета" }: { children: ReactNode; label?: string }) {
  const [vertical, setVertical] = useState(() => window.matchMedia('(min-width: 1024px)').matches);
  useEffect(() => {
    const media = window.matchMedia('(min-width: 1024px)');
    const onChange = (event: MediaQueryListEvent) => setVertical(event.matches);
    media.addEventListener('change', onChange);
    return () => media.removeEventListener('change', onChange);
  }, []);

  return (
    <div
      aria-label={label}
      aria-orientation={vertical ? 'vertical' : 'horizontal'}
      className="flex min-w-0 max-w-full gap-1 overflow-x-auto rounded-2xl border border-border bg-card p-1.5 shadow-sm lg:sticky lg:top-28 lg:flex-col lg:overflow-visible"
      role="tablist"
    >
      {children}
    </div>
  );
}

export function TabButton({ active, children, controls, id, onClick }: Props) {
  return (
    <button
      aria-controls={controls}
      aria-selected={active}
      className={cn(
        'inline-flex min-h-12 shrink-0 items-center justify-center gap-3 whitespace-nowrap rounded-xl px-4 py-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset lg:justify-start lg:whitespace-normal lg:text-left',
        active ? 'bg-secondary text-secondary-foreground' : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground',
      )}
      id={id}
      role="tab"
      tabIndex={active ? 0 : -1}
      type="button"
      onKeyDown={handleTabKeyDown}
      onClick={onClick}
    >
      {children}
    </button>
  );
}

export function TabPanel({ active, children, id, labelledBy }: { active: boolean; children: ReactNode; id: string; labelledBy: string }) {
  return (
    <div aria-labelledby={labelledBy} hidden={!active} id={id} role="tabpanel" tabIndex={0}>
      {children}
    </div>
  );
}
