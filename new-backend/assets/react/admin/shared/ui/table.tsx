import type { PropsWithChildren } from 'react';
import { cn } from '../lib';

export function AdminTable({ children }: PropsWithChildren) {
  return <table className="w-full border-collapse text-left text-sm">{children}</table>;
}

export function TableHead({ children }: PropsWithChildren) {
  return <thead className="border-b border-[#e4e5da] text-xs font-semibold text-[#789083]">{children}</thead>;
}

export function TableHeaderCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <th className={cn('px-4 py-4', className)}>{children}</th>;
}

export function TableRow({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <tr className={cn('border-b border-[#eceee5] transition hover:bg-[#f8f7f0]', className)}>{children}</tr>;
}

export function TableCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <td className={cn('px-4 py-4 align-middle', className)}>{children}</td>;
}
