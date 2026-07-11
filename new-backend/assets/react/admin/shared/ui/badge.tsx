import type { PropsWithChildren } from 'react';
import { cn } from '../lib';

type BadgeTone = 'amber' | 'blue' | 'gray' | 'green' | 'red';

const styles: Record<BadgeTone, string> = {
  amber: 'bg-[#faeed7] text-[#b36a08]',
  blue: 'bg-[#dceafe] text-[#2563eb]',
  gray: 'bg-[#edf0e8] text-[#667368]',
  green: 'bg-[#e5f3e9] text-[#2f7d4b]',
  red: 'bg-[#f7e2e2] text-[#a33d3d]',
};

export function Badge({ children, className = '', tone = 'green' }: PropsWithChildren<{ className?: string; tone?: BadgeTone }>) {
  return <span className={cn('inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', styles[tone], className)}>{children}</span>;
}
