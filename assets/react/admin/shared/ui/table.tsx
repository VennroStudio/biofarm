import type { PropsWithChildren } from 'react';
import { cn } from '../lib';

export function AdminTable({ children }: PropsWithChildren) {
  return <table className="w-full min-w-[680px] border-collapse text-left text-sm">{children}</table>;
}

export function TableHead({ children }: PropsWithChildren) {
  return <thead className="border-b border-[#dfece9] text-xs font-semibold text-[#5f7580]">{children}</thead>;
}

export function TableHeaderCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <th className={cn('bg-[#f5faf8] px-4 py-3', className)}>{children}</th>;
}

export function TableRow({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <tr className={cn('border-b border-[#dfece9] transition hover:bg-[#f5faf8]', className)}>{children}</tr>;
}

export function TableCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <td className={cn('px-4 py-3 align-middle', className)}>{children}</td>;
}
