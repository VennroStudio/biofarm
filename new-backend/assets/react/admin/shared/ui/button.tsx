import type { ButtonHTMLAttributes, PropsWithChildren } from 'react';
import { cn } from '../lib';

type ButtonVariant = 'danger' | 'ghost' | 'outline' | 'primary' | 'secondary';
type ButtonSize = 'icon' | 'md' | 'sm';

type ButtonProps = PropsWithChildren<ButtonHTMLAttributes<HTMLButtonElement> & {
  size?: ButtonSize;
  variant?: ButtonVariant;
}>;

const variants: Record<ButtonVariant, string> = {
  danger: 'bg-[#b94b4b] text-white hover:bg-[#a13f3f]',
  ghost: 'bg-transparent text-[#53685c] hover:bg-[#eef1e8]',
  outline: 'border border-[#d9dece] bg-white text-[#26382d] hover:bg-[#f8f7f0]',
  primary: 'bg-[#2f7d4b] text-white hover:bg-[#276b40]',
  secondary: 'bg-[#e5f3e9] text-[#2f7d4b] hover:bg-[#d8ecdf]',
};

const sizes: Record<ButtonSize, string> = {
  icon: 'h-9 w-9 p-0',
  md: 'h-11 px-5',
  sm: 'h-9 px-3 text-sm',
};

export function Button({ children, className, size = 'md', type = 'button', variant = 'primary', ...props }: ButtonProps) {
  return (
    <button
      className={cn(
        'inline-flex items-center justify-center gap-2 rounded-md text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
        variants[variant],
        sizes[size],
        className,
      )}
      type={type}
      {...props}
    >
      {children}
    </button>
  );
}
