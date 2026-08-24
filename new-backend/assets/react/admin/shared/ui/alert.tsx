import type { ReactNode } from 'react';
import { cn } from '../lib';

type ErrorAlertProps = {
  children?: ReactNode;
  className?: string;
};

export function ErrorAlert({ children, className }: ErrorAlertProps) {
  if (!children) {
    return null;
  }

  return (
    <div
      role="alert"
      className={cn('rounded-md border border-[#f0c9c9] bg-[#fff5f5] px-4 py-3 text-sm font-semibold text-[#9f3b3b]', className)}
    >
      {children}
    </div>
  );
}
