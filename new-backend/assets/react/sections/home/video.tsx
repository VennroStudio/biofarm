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
    if (!openButton || !frame || !dialog) {
      return undefined;
    }

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

      requestAnimationFrame(() => {
        modalElement.style.opacity = '1';
        dialog.style.opacity = '1';
        dialog.style.transform = 'scale(1)';
        dialog.focus();
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
      openButton.focus();
    };

    const closeOnBackdrop = (event: MouseEvent) => {
      if (event.target instanceof Node && !dialog.contains(event.target)) {
        close();
      }
    };

    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && !modalElement.hidden) {
        close();
      }
    };

    openButton.addEventListener('click', open);
    modalElement.addEventListener('mousedown', closeOnBackdrop);
    window.addEventListener('keydown', closeOnEscape);
    modalElement.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((button) => {
      button.addEventListener('click', close);
    });

    return () => {
      openButton.removeEventListener('click', open);
      modalElement.removeEventListener('mousedown', closeOnBackdrop);
      window.removeEventListener('keydown', closeOnEscape);
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
