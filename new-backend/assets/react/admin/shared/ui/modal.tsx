import { X } from 'lucide-react';
import { createPortal } from 'react-dom';
import type { PropsWithChildren, ReactNode } from 'react';
import { useEffect, useRef } from 'react';
import { Button } from './button';

const modalStack: HTMLElement[] = [];
let bodyOverflow = '';
function updateModalStack() {
  modalStack.forEach((dialog, index) => {
    const container = dialog.parentElement;
    if (container) {
      container.inert = index !== modalStack.length - 1;
      container.style.zIndex = String(50 + index);
    }
  });
}

type ModalProps = PropsWithChildren<{
  description?: ReactNode;
  footer?: ReactNode;
  headerContent?: ReactNode;
  maxWidth?: string;
  onClose: () => void;
  open: boolean;
  title: string;
}>;

export function Modal({ children, description, footer, headerContent, maxWidth = 'max-w-2xl', onClose, open, title }: ModalProps) {
  const dialogRef = useRef<HTMLElement>(null);
  const onCloseRef = useRef(onClose);
  const previouslyFocusedRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    onCloseRef.current = onClose;
  }, [onClose]);

  useEffect(() => {
    if (!open) {
      return undefined;
    }

    previouslyFocusedRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    const dialog = dialogRef.current;
    if (!dialog) return;
    if (modalStack.length === 0) bodyOverflow = document.body.style.overflow;
    modalStack.push(dialog);
    updateModalStack();
    document.body.style.overflow = 'hidden';

    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const focusFirst = () => {
      if (modalStack.at(-1) === dialog) (dialog.querySelector<HTMLElement>(focusableSelector) ?? dialog).focus();
    };
    const animationFrame = window.requestAnimationFrame(focusFirst);

    const handleKeyDown = (event: KeyboardEvent) => {
      if (modalStack.at(-1) !== dialog || event.defaultPrevented) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        onCloseRef.current();
        return;
      }

      if (event.key === 'Tab' && dialogRef.current) {
        const focusable = Array.from(dialogRef.current.querySelectorAll<HTMLElement>(focusableSelector)).filter(element => element.getClientRects().length > 0 && !element.matches(':disabled'));
        if (focusable.length === 0) {
          event.preventDefault();
          dialogRef.current.focus();
          return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (!dialog.contains(document.activeElement)) {
          event.preventDefault();
          first.focus();
        } else if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    return () => {
      window.cancelAnimationFrame(animationFrame);
      document.removeEventListener('keydown', handleKeyDown);
      const wasTop = modalStack.at(-1) === dialog;
      const index = modalStack.indexOf(dialog);
      if (index >= 0) modalStack.splice(index, 1);
      updateModalStack();
      if (modalStack.length === 0) document.body.style.overflow = bodyOverflow;
      if (wasTop && previouslyFocusedRef.current?.isConnected) previouslyFocusedRef.current.focus();
    };
  }, [open]);

  if (!open) {
    return null;
  }

  return createPortal(
    <div className="fixed inset-0 z-50 grid place-items-center px-4 py-8">
      <button type="button" aria-label="Закрыть" className="absolute inset-0 bg-[#101812]/55" onClick={onClose} />
      <section
        ref={dialogRef}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        tabIndex={-1}
        className={`relative z-10 flex max-h-[calc(100dvh-2rem)] w-full ${maxWidth} flex-col overflow-hidden rounded-2xl border border-[#dfece9] bg-white shadow-[0_24px_80px_rgba(41,69,85,0.2)] sm:max-h-[90vh]`}
      >
        <div className="flex items-start justify-between gap-4 border-b border-[#dfece9] px-4 py-4 sm:px-6">
          <div>
            <h2 className="text-xl font-bold text-[#294555]">{title}</h2>
            {description && <p className="mt-1 text-sm text-[#5f7580]">{description}</p>}
          </div>
          <Button type="button" variant="ghost" size="icon" onClick={onClose} aria-label="Закрыть">
            <X className="h-4 w-4" />
          </Button>
        </div>
        {headerContent && <div className="shrink-0 border-b border-[#dfece9]">{headerContent}</div>}
        <div className="overscroll-contain overflow-y-auto px-4 py-4 sm:px-6">{children}</div>
        {footer && <div className="flex flex-wrap justify-end gap-2 border-t border-[#dfece9] px-4 py-3 sm:px-6">{footer}</div>}
      </section>
    </div>,
    document.body,
  );
}
