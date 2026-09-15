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
  ghost: 'bg-transparent text-[#526d78] hover:bg-[#eaf5f1] hover:text-[#18574f]',
  outline: 'border border-[#cfe2de] bg-white text-[#294555] hover:border-[#2e8175] hover:bg-[#f5faf8]',
  primary: 'bg-[#2e8175] text-white hover:bg-[#236b62]',
  secondary: 'bg-[#eaf5f1] text-[#2e8175] hover:bg-[#dcefea]',
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
        'inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2e8175] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
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
