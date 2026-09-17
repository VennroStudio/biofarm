import { useEffect, useId, useRef } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';
import { LinkQR } from '../../../program/shared';

export function OfferLinkDialog({ url, onClose }: { url: string; onClose: () => void }) {
    const ref = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;
        const previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialog.showModal();
        return () => {
            dialog.close();
            document.body.style.overflow = previousOverflow;
            previouslyFocused?.focus();
        };
    }, []);

    return createPortal(
        <dialog ref={ref} aria-labelledby={titleId}
            className="fixed inset-0 m-auto max-h-[90dvh] w-[calc(100%_-_2rem)] max-w-md overflow-y-auto rounded-2xl border border-border bg-white p-5 text-foreground shadow-xl backdrop:bg-foreground/45 sm:p-6"
            onCancel={(event) => { event.preventDefault(); onClose(); }}
            onClick={(event) => {
                if (event.target !== event.currentTarget) return;
                const rect = event.currentTarget.getBoundingClientRect();
                if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) onClose();
            }}>
            <div className="mb-2 flex items-center justify-between gap-4">
                <h2 id={titleId} className="text-xl text-primary">Ссылка на корзину</h2>
                <button type="button" aria-label="Закрыть окно" title="Закрыть"
                    className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full hover:bg-secondary focus-visible:outline-primary"
                    onClick={onClose}><X className="h-5 w-5" aria-hidden="true" /></button>
            </div>
            <p className="mb-4 text-sm text-muted-foreground">Сканируйте QR-код или отправьте ссылку покупателю.</p>
            <LinkQR url={url} centered />
        </dialog>, document.body,
    );
}
