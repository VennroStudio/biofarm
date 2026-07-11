import type { AnchorHTMLAttributes, ButtonHTMLAttributes, InputHTMLAttributes, PropsWithChildren, ReactNode, TextareaHTMLAttributes } from 'react';

type ClassValue = false | null | string | undefined;

export function cn(...values: ClassValue[]) {
  return values.filter(Boolean).join(' ');
}

type ButtonProps = PropsWithChildren<ButtonHTMLAttributes<HTMLButtonElement> & {
  size?: 'icon' | 'lg' | 'md' | 'sm';
  variant?: 'destructive' | 'ghost' | 'outline' | 'primary' | 'secondary';
}>;

const buttonVariants = {
  destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
  ghost: 'bg-transparent hover:bg-accent hover:text-accent-foreground',
  outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
  primary: 'gradient-primary text-primary-foreground hover:opacity-95',
  secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
};

const buttonSizes = {
  icon: 'h-10 w-10',
  lg: 'h-11 rounded-md px-8',
  md: 'h-10 px-4 py-2',
  sm: 'h-9 rounded-md px-3',
};

export function Button({ children, className, size = 'md', type = 'button', variant = 'primary', ...props }: ButtonProps) {
  return (
    <button
      className={cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        buttonVariants[variant],
        buttonSizes[size],
        className,
      )}
      type={type}
      {...props}
    >
      {children}
    </button>
  );
}

type LinkButtonProps = PropsWithChildren<AnchorHTMLAttributes<HTMLAnchorElement> & {
  size?: ButtonProps['size'];
  variant?: ButtonProps['variant'];
}>;

export function LinkButton({ children, className, size = 'md', variant = 'primary', ...props }: LinkButtonProps) {
  return (
    <a
      className={cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        buttonVariants[variant],
        buttonSizes[size],
        className,
      )}
      {...props}
    >
      {children}
    </a>
  );
}

export function Card({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <section className={cn('rounded-xl border bg-card text-card-foreground shadow-premium', className)}>{children}</section>;
}

export function CardHeader({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <div className={cn('flex flex-col space-y-1.5 p-6', className)}>{children}</div>;
}

export function CardTitle({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <h2 className={cn('text-2xl font-semibold leading-none tracking-normal', className)}>{children}</h2>;
}

export function CardDescription({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <p className={cn('text-sm text-muted-foreground', className)}>{children}</p>;
}

export function CardContent({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <div className={cn('p-6 pt-0', className)}>{children}</div>;
}

export function CardFooter({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <div className={cn('flex items-center p-6 pt-0', className)}>{children}</div>;
}

export function Separator({ className }: { className?: string }) {
  return <div className={cn('h-px w-full bg-border', className)} />;
}

export function Label({ children, htmlFor, className }: { children: ReactNode; htmlFor?: string; className?: string }) {
  return <label className={cn('text-sm font-medium leading-none', className)} htmlFor={htmlFor}>{children}</label>;
}

export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={cn(
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-shadow file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    />
  );
}

export function Textarea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return (
    <textarea
      className={cn(
        'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background transition-shadow placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    />
  );
}

export function Badge({ children, className, variant = 'default' }: PropsWithChildren<{ className?: string; variant?: 'default' | 'outline' | 'secondary' }>) {
  const variants = {
    default: 'border-transparent bg-primary text-primary-foreground',
    outline: 'border-border text-foreground',
    secondary: 'border-transparent bg-secondary text-secondary-foreground',
  };

  return (
    <span className={cn('inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors', variants[variant], className)}>
      {children}
    </span>
  );
}
