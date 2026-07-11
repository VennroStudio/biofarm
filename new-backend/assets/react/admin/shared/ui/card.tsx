import type { PropsWithChildren } from 'react';
import { cn } from '../lib';

export function Card({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <section className={cn('rounded-lg border border-[#e4e5da] bg-white shadow-sm', className)}>{children}</section>;
}
