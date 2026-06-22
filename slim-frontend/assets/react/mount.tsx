import { createRoot } from 'react-dom/client';
import { ProductCommandPanel } from './islands/ProductCommandPanel';
import { ProductCounter } from './islands/ProductCounter';

document.querySelectorAll('[data-react-island="product-counter"]').forEach((element) => {
  const htmlElement = element as HTMLElement;
  const rootElement = htmlElement.closest<HTMLElement>('[data-product-counter-root]');
  const counterElement = rootElement?.querySelector<HTMLElement>(
    htmlElement.dataset.counterSelector || '[data-product-counter]',
  );

  if (counterElement) {
    createRoot(htmlElement).render(<ProductCounter counterElement={counterElement} />);
  }
});

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
