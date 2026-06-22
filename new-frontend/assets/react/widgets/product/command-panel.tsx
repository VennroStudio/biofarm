import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type CommandAction = {
  title: string;
  method: string;
  endpoint: string;
};

type ProductResult = {
  id: number;
  title: string;
  price: number;
  description: string;
  category: string;
  brand: string;
  stock: number;
  image: string;
};

type DeleteResult = {
  id: number;
  deleted: boolean;
  message: string;
};

type CommandResult = {
  ok: boolean;
  message: string;
  action: CommandAction;
  product: ProductResult | null;
  delete: DeleteResult | null;
};

type Props = {
  feedbackSelector: string;
  modalSelector: string;
  panelSelector: string;
};

const money = new Intl.NumberFormat('en-US', {
  currency: 'USD',
  style: 'currency',
});

const text = (root: HTMLElement, selector: string, value: string) => {
  const element = root.querySelector(selector);
  if (element) {
    element.textContent = value;
  }
};

const hidden = (root: HTMLElement, selector: string, value: boolean) => {
  const element = root.querySelector<HTMLElement>(selector);
  if (element) {
    element.hidden = value;
  }
};

const setStatus = (feedback: HTMLElement, title: string | null) => {
  hidden(feedback, '[data-command-status]', title === null);
  if (title !== null) {
    text(feedback, '[data-command-status-title]', title);
  }
};

const setToast = (feedback: HTMLElement, result: CommandResult | null, onDetails: () => void) => {
  const toast = feedback.querySelector<HTMLElement>('[data-command-toast]');
  const details = feedback.querySelector<HTMLButtonElement>('[data-command-toast-details]');

  if (!toast) {
    return;
  }

  toast.hidden = result === null;
  toast.classList.toggle('is-success', result?.ok === true);
  toast.classList.toggle('is-error', result?.ok === false);

  if (result !== null) {
    text(feedback, '[data-command-toast-message]', result.message);
  }

  if (details) {
    details.onclick = onDetails;
  }
};

const closeModal = (modal: HTMLElement) => {
  modal.hidden = true;
};

const openModal = (modal: HTMLElement, result: CommandResult) => {
  modal.hidden = false;
  modal.classList.toggle('is-success', result.ok);
  modal.classList.toggle('is-error', !result.ok);

  text(modal, '[data-modal-eyebrow]', `${result.action.method} ${result.action.endpoint}`);
  text(modal, '[data-modal-title]', result.action.title);
  text(modal, '[data-command-result-status]', result.ok ? 'Command completed' : 'Command failed');
  text(modal, '[data-command-result-text]', result.message);

  const product = result.product;
  hidden(modal, '[data-command-result-product]', product === null);
  if (product) {
    const image = modal.querySelector<HTMLImageElement>('[data-command-product-image]');
    if (image) {
      image.src = product.image;
      image.alt = product.title;
    }

    text(modal, '[data-command-product-brand]', product.brand);
    text(modal, '[data-command-product-title]', product.title);
    text(modal, '[data-command-product-description]', product.description);
    text(modal, '[data-command-product-price]', money.format(product.price));
    text(modal, '[data-command-product-meta]', `${product.category} · stock ${product.stock}`);
  }

  const deleteResult = result.delete;
  hidden(modal, '[data-command-result-delete]', deleteResult === null);
  if (deleteResult) {
    text(modal, '[data-command-delete-title]', `Product #${deleteResult.id}`);
    text(modal, '[data-command-delete-status]', deleteResult.deleted ? 'Deleted' : 'Not deleted');
    text(modal, '[data-command-delete-message]', deleteResult.message);
  }

  modal.querySelector<HTMLElement>('.modal__dialog')?.focus();
};

function ProductCommandPanel({ feedbackSelector, modalSelector, panelSelector }: Props) {
  useEffect(() => {
    const panel = document.querySelector<HTMLElement>(panelSelector);
    const feedback = document.querySelector<HTMLElement>(feedbackSelector);
    const modal = document.querySelector<HTMLElement>(modalSelector);
    if (!panel || !feedback || !modal) {
      return undefined;
    }

    let lastResult: CommandResult | null = null;
    const showResult = (result: CommandResult) => {
      lastResult = result;
      openModal(modal, result);
      setToast(feedback, result, () => lastResult && openModal(modal, lastResult));
    };

    const submitForm = async (form: HTMLFormElement) => {
      const body = new URLSearchParams();
      new FormData(form).forEach((value, key) => {
        if (typeof value === 'string') {
          body.append(key, value);
        }
      });

      setStatus(feedback, form.dataset.commandTitle || 'Product command');

      try {
        const response = await fetch(form.action, {
          body,
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
          },
          method: form.method.toUpperCase(),
        });
        showResult((await response.json()) as CommandResult);
      } catch {
        showResult({
          action: {
            endpoint: form.action,
            method: form.method.toUpperCase(),
            title: form.dataset.commandTitle || 'Product command',
          },
          delete: null,
          message: 'Command request failed in the browser. Check network connection and try again.',
          ok: false,
          product: null,
        });
      } finally {
        setStatus(feedback, null);
      }
    };

    const forms = Array.from(panel.querySelectorAll<HTMLFormElement>('form[data-product-command-form]'));
    const handleSubmit = (event: SubmitEvent) => {
      event.preventDefault();
      void submitForm(event.currentTarget as HTMLFormElement);
    };
    const close = () => closeModal(modal);
    const closeOnBackdrop = (event: MouseEvent) => {
      if (event.target === modal) {
        close();
      }
    };
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        close();
      }
    };

    forms.forEach((form) => form.addEventListener('submit', handleSubmit));
    modal.addEventListener('mousedown', closeOnBackdrop);
    modal.querySelector<HTMLElement>('.modal__dialog')?.addEventListener('keydown', closeOnEscape);
    modal.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((button) => {
      button.addEventListener('click', close);
    });

    return () => {
      forms.forEach((form) => form.removeEventListener('submit', handleSubmit));
      modal.removeEventListener('mousedown', closeOnBackdrop);
      modal.querySelector<HTMLElement>('.modal__dialog')?.removeEventListener('keydown', closeOnEscape);
      modal.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((button) => {
        button.removeEventListener('click', close);
      });
    };
  }, [feedbackSelector, modalSelector, panelSelector]);

  return null;
}

export function mountProductCommandPanel() {
  document.querySelectorAll('[data-react-island="product-command-panel"]').forEach((element) => {
    const htmlElement = element as HTMLElement;

    createRoot(htmlElement).render(
      <ProductCommandPanel
        feedbackSelector={htmlElement.dataset.feedbackSelector || '[data-command-feedback]'}
        panelSelector={htmlElement.dataset.panelSelector || '#product-commands'}
        modalSelector={htmlElement.dataset.modalSelector || '[data-modal="product-command-result"]'}
      />,
    );
  });
}
