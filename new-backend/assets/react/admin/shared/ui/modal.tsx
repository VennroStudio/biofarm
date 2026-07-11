import { X } from 'lucide-react';
import type { PropsWithChildren, ReactNode } from 'react';
import { Button } from './button';

type ModalProps = PropsWithChildren<{
  description?: ReactNode;
  footer?: ReactNode;
  maxWidth?: string;
  onClose: () => void;
  open: boolean;
  title: string;
}>;

export function Modal({ children, description, footer, maxWidth = 'max-w-2xl', onClose, open, title }: ModalProps) {
  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center px-4 py-8">
      <button type="button" aria-label="Закрыть" className="absolute inset-0 bg-[#101812]/55" onClick={onClose} />
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
