import { type KeyboardEvent, type ReactNode } from 'react';
import { cn } from '../../../site/ui';

type Props = {
  active: boolean;
  children: ReactNode;
  controls: string;
  id: string;
  onClick: () => void;
};

function handleTabKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
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
      : (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
  tabs[nextIndex].focus();
  tabs[nextIndex].click();
}

export function TabButton({ active, children, controls, id, onClick }: Props) {
  return (
    <button
      aria-controls={controls}
      aria-selected={active}
      className={cn(
        'inline-flex min-h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors',
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
