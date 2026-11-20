import type { PropsWithChildren } from 'react';
import { cn } from '../lib';

export function Card({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <section className={cn('rounded-2xl border border-[#dfece9] bg-white shadow-[0_8px_28px_rgba(41,69,85,0.05)]', className)}>{children}</section>;
}
