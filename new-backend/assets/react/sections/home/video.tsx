import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type Props = {
  modalElement: HTMLElement;
  rootElement: HTMLElement;
};

function HomeVideo({ modalElement, rootElement }: Props) {
  useEffect(() => {
    const openButton = rootElement.querySelector<HTMLButtonElement>('[data-home-video-open]');
    const frame = modalElement.querySelector<HTMLIFrameElement>('[data-home-video-frame]');
    const dialog = modalElement.querySelector<HTMLElement>('[data-modal-dialog]');
    const closeButton = modalElement.querySelector<HTMLButtonElement>('[data-modal-close]');
    if (!openButton || !frame || !dialog || !closeButton) {
      return undefined;
    }

    const focusableSelector = 'a[href], button:not([disabled]), iframe, input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const open = () => {
      const src = frame.dataset.src;
      if (src && frame.src !== src) {
        frame.src = src;
      }

      modalElement.style.opacity = '0';
      modalElement.style.transition = 'opacity 200ms ease';
      dialog.style.opacity = '0';
      dialog.style.transform = 'scale(0.95)';
      dialog.style.transition = 'opacity 200ms ease, transform 200ms ease';
      modalElement.hidden = false;
      document.documentElement.classList.add('overflow-hidden');
      document.body.classList.add('overflow-hidden');

      requestAnimationFrame(() => {
        modalElement.style.opacity = '1';
        dialog.style.opacity = '1';
        dialog.style.transform = 'scale(1)';
        closeButton.focus();
      });
    };

    const close = () => {
      modalElement.hidden = true;
      modalElement.style.opacity = '';
      modalElement.style.transition = '';
      dialog.style.opacity = '';
      dialog.style.transform = '';
      dialog.style.transition = '';
      frame.removeAttribute('src');
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
      openButton.focus();
    };

    const closeOnBackdrop = (event: MouseEvent) => {
      if (event.target instanceof Node && !dialog.contains(event.target)) {
        close();
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (modalElement.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        close();
        return;
      }

      if (event.key !== 'Tab') {
        return;
      }

      const focusable = Array.from(dialog.querySelectorAll<HTMLElement>(focusableSelector));
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (!first || !last) {
        event.preventDefault();
        dialog.focus();
      } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    const containFocus = (event: FocusEvent) => {
      if (!modalElement.hidden && event.target instanceof Node && !dialog.contains(event.target)) {
        closeButton.focus();
      }
    };

    openButton.addEventListener('click', open);
    modalElement.addEventListener('mousedown', closeOnBackdrop);
    window.addEventListener('keydown', handleKeyDown);
    document.addEventListener('focusin', containFocus);
    modalElement.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((button) => {
      button.addEventListener('click', close);
    });

    return () => {
      openButton.removeEventListener('click', open);
      modalElement.removeEventListener('mousedown', closeOnBackdrop);
      window.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('focusin', containFocus);
      modalElement.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((button) => {
        button.removeEventListener('click', close);
      });
    };
  }, [modalElement, rootElement]);

  return null;
}

export function mountHomeVideo() {
  document.querySelectorAll('[data-react-island="home-video"]').forEach((element) => {
    const htmlElement = element as HTMLElement;
    if (htmlElement.dataset.mounted === 'true') {
      return;
    }

    htmlElement.dataset.mounted = 'true';
    const rootElement = htmlElement.closest<HTMLElement>(
      htmlElement.dataset.rootSelector || '[data-home-video-root]',
    );
    const modalElement = rootElement?.querySelector<HTMLElement>(
      htmlElement.dataset.modalSelector || '[data-modal="home-production-video"]',
    );

    if (rootElement && modalElement) {
      createRoot(htmlElement).render(<HomeVideo modalElement={modalElement} rootElement={rootElement} />);
    }
  });
}
