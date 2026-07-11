import { X } from 'lucide-react';
import type { ButtonHTMLAttributes, PropsWithChildren, ReactNode } from 'react';

type ButtonProps = PropsWithChildren<ButtonHTMLAttributes<HTMLButtonElement> & {
  type?: 'button' | 'submit';
  variant?: 'primary' | 'outline' | 'ghost' | 'danger' | 'secondary';
  size?: 'md' | 'sm' | 'icon';
  className?: string;
}>;

export function Button({ children, type = 'button', variant = 'primary', size = 'md', className = '', ...props }: ButtonProps) {
  const styles = {
    primary: 'bg-[#2f7d4b] text-white hover:bg-[#256a3e]',
    outline: 'border border-[#d9dece] bg-white text-[#26382d] hover:bg-[#f8f7f0]',
    ghost: 'text-[#53685c] hover:bg-[#eef1e8] hover:text-[#26382d]',
    danger: 'bg-[#b94b4b] text-white hover:bg-[#963b3b]',
    secondary: 'bg-[#eef1e8] text-[#26382d] hover:bg-[#e2e6dc]',
  };
  const sizes = {
    md: 'h-10 px-4',
    sm: 'h-8 px-3 text-xs',
    icon: 'h-9 w-9 px-0',
  };

  return (
    <button
      type={type}
      className={`inline-flex items-center justify-center gap-2 rounded-md text-sm font-semibold transition ${sizes[size]} ${styles[variant]} disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
      {...props}
    >
      {children}
    </button>
  );
}

export function Card({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <section className={`rounded-lg border border-[#e4e5da] bg-white shadow-sm ${className}`}>{children}</section>;
}

export function Field({ label, children }: PropsWithChildren<{ label: ReactNode }>) {
  return (
    <label className="grid gap-1.5 text-sm font-semibold text-[#26382d]">
      <span>{label}</span>
      {children}
    </label>
  );
}

export const inputClass =
  'h-10 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 text-sm text-[#26382d] outline-none transition placeholder:text-[#91a094] focus:border-[#2f7d4b] focus:bg-white';

export const textareaClass =
  'min-h-28 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 py-2 text-sm text-[#26382d] outline-none transition placeholder:text-[#91a094] focus:border-[#2f7d4b] focus:bg-white';

export function EmptyState({ children }: PropsWithChildren) {
  return <div className="rounded-lg border border-dashed border-[#d9dece] p-8 text-center text-[#728476]">{children}</div>;
}

export function PageHeader({ title, subtitle, actions }: { title: string; subtitle?: ReactNode; actions?: ReactNode }) {
  return (
    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 className="text-3xl font-bold text-[#1f3328]">{title}</h1>
        {subtitle && <p className="mt-1 text-[#789083]">{subtitle}</p>}
      </div>
      {actions}
    </div>
  );
}

export function Badge({ children, tone = 'green', className = '' }: PropsWithChildren<{ tone?: 'green' | 'amber' | 'red' | 'gray' | 'blue'; className?: string }>) {
  const styles = {
    green: 'bg-[#e5f3e9] text-[#2f7d4b]',
    amber: 'bg-[#faeed7] text-[#b36a08]',
    red: 'bg-[#f7e2e2] text-[#a33d3d]',
    gray: 'bg-[#edf0e8] text-[#667368]',
    blue: 'bg-[#dceafe] text-[#2563eb]',
  };

  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${styles[tone]} ${className}`}>{children}</span>;
}

export function Modal({
  open,
  title,
  description,
  children,
  footer,
  maxWidth = 'max-w-2xl',
  onClose,
}: PropsWithChildren<{
  open: boolean;
  title: string;
  description?: ReactNode;
  footer?: ReactNode;
  maxWidth?: string;
  onClose: () => void;
}>) {
  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center px-4 py-8">
      <button
        type="button"
        aria-label="Закрыть"
        className="absolute inset-0 bg-[#101812]/55"
        onClick={onClose}
      />
      <section
        role="dialog"
        aria-modal="true"
        aria-label={title}
        className={`relative z-10 flex max-h-[90vh] w-full ${maxWidth} flex-col overflow-hidden rounded-lg border border-[#e4e5da] bg-white shadow-xl`}
      >
        <div className="flex items-start justify-between gap-4 border-b border-[#eceee5] px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-[#1f3328]">{title}</h2>
            {description && <p className="mt-1 text-sm text-[#789083]">{description}</p>}
          </div>
          <Button type="button" variant="ghost" size="icon" onClick={onClose} aria-label="Закрыть">
            <X className="h-4 w-4" />
          </Button>
        </div>
        <div className="overflow-y-auto px-6 py-5">{children}</div>
        {footer && <div className="flex justify-end gap-2 border-t border-[#eceee5] px-6 py-4">{footer}</div>}
      </section>
    </div>
  );
}

export function AdminTable({ children }: PropsWithChildren) {
  return <table className="w-full border-collapse text-left text-sm">{children}</table>;
}

export function TableHead({ children }: PropsWithChildren) {
  return <thead className="border-b border-[#e4e5da] text-xs font-semibold text-[#789083]">{children}</thead>;
}

export function TableRow({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <tr className={`border-b border-[#eceee5] transition last:border-0 hover:bg-[#f8f7f0] ${className}`}>{children}</tr>;
}

export function TableCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <td className={`px-4 py-4 align-middle ${className}`}>{children}</td>;
}

export function TableHeaderCell({ children, className = '' }: PropsWithChildren<{ className?: string }>) {
  return <th className={`px-4 py-4 align-middle ${className}`}>{children}</th>;
}
